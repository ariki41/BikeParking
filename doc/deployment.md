# 開発サーバーへのデプロイ

`main` への push は GitHub Actions でテスト、Viteアセットのビルド、本番用OCIイメージのビルドを実行します。開発環境へのデプロイは通常のpushでは行わず、Actionsの手動実行で明示的に指定した場合だけ実施します。成功したイメージは GitHub Container Registry (GHCR) へ公開され、その**digest**を指定して `development` Environment のサーバーへデプロイします。サーバー上でソースをビルドしないため、CIで検証した成果物とデプロイされる成果物は同一です。開発環境はDocker内のTailscale Funnelサイドカーを経由し、`https://bikeparking-dev.tail06f222.ts.net` としてインターネットへ公開します。

## ワークフローの構成

| ジョブ | 実行条件 | 内容 |
| --- | --- | --- |
| `test` | Pull Request、`main` へのpush、手動実行 | MySQLを使ったテスト、Pint、Viteアセットビルド |
| `image` | `test` 成功後 | 本番用Dockerイメージをビルド。`main` ではGHCRへ公開 |
| `deploy-development` | `main` の手動実行で `開発環境へデプロイする` を有効化した場合だけ | Tailscale経由SSHで開発サーバーへデプロイ |

デプロイジョブの最初にSSH、`x86_64`、Docker、Docker Compose、デプロイ先ディレクトリ、および5 GiB以上の空き容量を確認します。サーバーが停止中などで接続できない場合は、その時点で失敗して終了し、サーバー上のファイル・コンテナは変更しません。

## 通常のデプロイ・停止・復旧

サーバーの起動は別途行い、起動後にTailscaleへ参加していることを確認します。開発環境へデプロイする場合は、GitHubの **Actions** から **CI/CD** を選び、ブランチに `main` を指定し、`開発環境へデプロイする` を有効にして **Run workflow** を実行します。入力を有効にしない手動実行や `main` への通常pushでは、テストとイメージ作成だけが実行されます。

サーバーが停止中または接続不能な場合、デプロイはSSH接続から10秒以内に中止します。この確認より後のイメージ取得・マイグレーション・コンテナ更新は行いません。停止する場合は、必要に応じてサーバー上でアプリケーションを先に停止します。

```bash
cd /opt/bike-parking
docker compose -f compose.deploy.yml stop app scheduler tailscale
```

アプリケーション更新後にヘルスチェックが失敗すると、デプロイスクリプトは直前のdigestのアプリケーションコンテナへ自動で戻します。初回デプロイで戻すイメージがない場合は、異常なアプリケーションコンテナを停止して終了します。データベースマイグレーションは前方互換で作成することを前提とします。

## 開発サーバーの初期設定

対象は `kaede.tail06f222.ts.net` 上のUbuntu x86_64サーバーです。ホストのTailscaleはGitHub ActionsからのSSH接続に利用します。アプリの外部公開は、Docker内のTailscaleサイドカーが担当します。OpenSSH Server、Docker EngineとDocker Compose v2をあらかじめ導入し、`ariki` ユーザーでSSH接続できるようにします。

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

`.env` はGitへ追加せず、サーバー上でだけ保管してください。`TS_AUTHKEY` はサーバーへ手入力せず、GitHub Environment `development` のSecretからデプロイ時に同期します。GitHub Actionsを使わずサーバー上で手動デプロイする場合だけ、サーバーの `.env` にも設定してください。

`APP_URL` は `https://bikeparking-dev.tail06f222.ts.net`、`TS_HOSTNAME` は `bikeparking-dev` に設定します。アプリコンテナはホストのポートを公開しません。TailscaleサイドカーがDockerネットワーク内の `app:80` へHTTPSで転送するため、外部公開の経路はFunnelだけです。

### Tailscale Funnelの初期設定

Tailscale管理画面でMagicDNSとHTTPSを有効にし、TailscaleのAuth keyを次のタグで作成します。

```text
tag:bike-parking-development
```

Access controlsには、このタグ付きノードだけがFunnelを使えるよう次の設定を追加します。`tagOwners` の所有者は利用しているTailnetの運用方針に合わせて指定してください。

```json
{
  "tagOwners": {
    "tag:bike-parking-development": ["autogroup:admin"]
  },
  "nodeAttrs": [
    {
      "target": ["tag:bike-parking-development"],
      "attr": ["funnel"]
    }
  ]
}
```

初回デプロイ後に、`https://bikeparking-dev.tail06f222.ts.net/up` と `docker compose -f compose.deploy.yml exec tailscale tailscale funnel status --json` を確認してください。Funnelの状態は `tailscale-state` ボリュームに保持され、コンテナの再作成後も同じホスト名で再接続します。

### 開発用テストデータ

GitHub Actionsによるkaedeへのデプロイでは、開発用テストデータを常に投入します。テストユーザーのパスワードはGitHub Environment `development` の `DEVELOPMENT_SEED_PASSWORD` Secretに設定してください。

サーバー上で手動デプロイする場合だけ、`.env` に以下を設定します。

```dotenv
SEED_DEVELOPMENT_DATA=true
DEVELOPMENT_SEED_PASSWORD=<開発用テストユーザーのパスワード>
```

デプロイ時、マイグレーション後に `DevelopmentSeeder` が実行されます。これは既存データを削除せず、次の固定データを再実行可能な形で投入・更新します。

- `development-owner` と `development-reviewer` のテストユーザー
- 東京駅前、神田駅東口、霞が関のテスト駐輪場
- 平日・休日料金、無料時間、最大料金なし、日付またぎ料金
- 駐輪場レビュー

`SEED_DEVELOPMENT_DATA` の既定値は `false` です。本番環境では設定せず、`DevelopmentSeeder` を実行しないでください。

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
| Secret | `DEVELOPMENT_SEED_PASSWORD` | 開発用テストユーザーのパスワード |
| Secret | `TS_AUTHKEY` | `tag:bike-parking-development` を付与した再利用可能なTailscale Auth key |

TailscaleのTrust credentialはGitHub ActionsをIssuerとし、Subjectを `repo:ariki41/BikeParking:environment:development`、タグを `tag:ci` とします。Scopesは **Devices → Core → Write** と **Keys → Auth Keys → Write** が必要です。

## デプロイ内容

サーバーでは `compose.deploy.yml` が次を管理します。

- `app`: Apache + PHP 8.3で動くLaravelアプリケーション。`/up` のヘルスチェックを通過するまでデプロイ完了としません。
- `scheduler`: Laravelスケジューラを常時実行し、24時間経過した駐輪場確認用の一時画像を1時間ごとに削除します。
- `tailscale`: `bikeparking-dev` としてTailnetへ参加し、Tailscale FunnelからDocker内部の `app:80` へHTTPSで転送します。ホストポートは公開しません。
- `mysql`: MySQL 8.0。データは名前付きボリューム `mysql-data` に保存されます。
- `app-storage`: アップロード画像などLaravelの永続ストレージです。

デプロイスクリプトは新しいdigestのイメージを取得してからMySQLの起動を待ち、`php artisan migrate --force` を実行します。`SEED_DEVELOPMENT_DATA=true` の場合は、その後に開発用テストデータを投入して、最後にアプリケーション、スケジューラ、Tailscale Funnelを更新します。アプリまたはFunnelのヘルスチェックに失敗した場合は、直前のアプリケーションイメージへ戻します。初回のFunnel用DNS・証明書設定には数分かかることがあります。

自動復旧後も手動で以前のdigestへ戻す必要がある場合は、サーバーで次のように実行できます。

```bash
cd /opt/bike-parking
IMAGE_NAME=ghcr.io/ariki41/bikeparking \
IMAGE_DIGEST=sha256:<戻したいdigest> \
bash deploy.sh /opt/bike-parking
```
