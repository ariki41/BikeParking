# 開発サーバーへのデプロイ

`main` への push は GitHub Actions でテスト、Viteアセットのビルド、本番用OCIイメージのビルドを実行します。成功したイメージは GitHub Container Registry (GHCR) へ公開され、その**digest**を指定して `development` Environment のサーバーへデプロイします。サーバー上でソースをビルドしないため、CIで検証した成果物とデプロイされる成果物は同一です。

## ワークフローの構成

| ジョブ | 実行条件 | 内容 |
| --- | --- | --- |
| `test` | Pull Request、`main` へのpush、手動実行 | MySQLを使ったテスト、Pint、Viteアセットビルド |
| `image` | `test` 成功後 | 本番用Dockerイメージをビルド。`main` ではGHCRへ公開 |
| `deploy-development` | `main` へのpushまたは手動実行 | Tailscale経由SSHで開発サーバーへデプロイ |

デプロイジョブの最初にSSH、`x86_64`、Docker、Docker Compose、デプロイ先ディレクトリ、および5 GiB以上の空き容量を確認します。サーバーが停止中などで接続できない場合は、その時点で失敗して終了し、サーバー上のファイル・コンテナは変更しません。

## 開発サーバーの初期設定

対象は `kaede.tail06f222.ts.net` 上のUbuntu x86_64サーバーです。Tailscale、OpenSSH Server、Docker EngineとDocker Compose v2をあらかじめ導入し、`ariki` ユーザーでSSH接続できるようにします。

デプロイ先ディレクトリを作成し、`ariki` が書き込めるようにします。

```bash
sudo install -d -o ariki -g ariki /opt/bike-parking
```

サーバーの `/opt/bike-parking/.env` は初回デプロイ前に作成します。リポジトリの `deploy.env.example` を元にし、少なくとも次の値を設定してください。

```dotenv
APP_KEY=base64:<32バイト乱数をBase64エンコードした値>
DB_PASSWORD=<MySQLアプリケーションユーザーの強いパスワード>
MYSQL_ROOT_PASSWORD=<MySQL root用の別の強いパスワード>
YOLP_CLIENT_ID=<Yahoo!地図APIのClient ID>
```

`APP_KEY` はPHPがあれば次のコマンドで生成できます。

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

`.env` はGitへ追加せず、サーバー上でだけ保管してください。

GHCRパッケージが非公開の場合、サーバーで `read:packages` 権限を持つGitHub Classic Personal Access Tokenを使い、一度ログインします。

```bash
echo '<GitHub PAT>' | docker login ghcr.io -u ariki41 --password-stdin
```

## GitHub Environment設定

リポジトリの `Settings` → `Environments` → `development` に、次を設定します。

| 種別 | 名前 | 値 |
| --- | --- | --- |
| Variable | `DEPLOY_HOST` | `kaede.tail06f222.ts.net` |
| Variable | `DEPLOY_USER` | `ariki` |
| Variable | `DEPLOY_PORT` | `22` |
| Variable | `DEPLOY_PATH` | `/opt/bike-parking` |
| Variable | `IMAGE_NAME` | `ghcr.io/ariki41/bikeparking` |
| Secret | `TS_OAUTH_CLIENT_ID` | TailscaleのOAuth client ID |
| Secret | `TS_AUDIENCE` | Tailscaleで発行したAudience |
| Secret | `DEPLOY_SSH_PRIVATE_KEY` | `ariki` ユーザーに登録したSSH秘密鍵 |
| Secret | `DEPLOY_KNOWN_HOSTS` | `kaede.tail06f222.ts.net` のED25519 known_hosts行 |

TailscaleのTrust credentialはGitHub ActionsをIssuerとし、Subjectを `repo:ariki41/BikeParking:environment:development`、タグを `tag:ci` とします。Scopesは **Devices → Core → Write** と **Keys → Auth Keys → Write** が必要です。

## デプロイ内容

サーバーでは `compose.deploy.yml` が次を管理します。

- `app`: Apache + PHP 8.3で動くLaravelアプリケーション。`/up` のヘルスチェックを通過するまでデプロイ完了としません。
- `mysql`: MySQL 8.0。データは名前付きボリューム `mysql-data` に保存されます。
- `app-storage`: アップロード画像などLaravelの永続ストレージです。

デプロイスクリプトは新しいdigestのイメージを取得してからMySQLの起動を待ち、`php artisan migrate --force` を実行して、最後にアプリケーションの更新とヘルスチェックを行います。

以前のdigestへ戻す必要がある場合は、サーバーで次のように実行できます。

```bash
cd /opt/bike-parking
IMAGE_NAME=ghcr.io/ariki41/bikeparking \
IMAGE_DIGEST=sha256:<戻したいdigest> \
bash deploy.sh /opt/bike-parking
```
