# KAGOYA Cloud 本番デプロイ

`main` への push は、テスト済みの OCI イメージを GHCR へ公開します。本番への反映は、GitHub Actions の手動実行で `本番環境へデプロイする` を有効にした場合だけ行います。アプリコンテナは `127.0.0.1:8000` にだけ公開し、公開TLS終端はホストOSのNginxが担います。

## 初回準備

KAGOYA Cloud の Ubuntu x86_64 サーバーに Docker Engine と Docker Compose v2、OpenSSH を導入し、時刻同期を有効にします。Firewall は HTTP (80)、HTTPS (443)、および管理元を限定した SSH だけを許可します。DNS の A/AAAA レコードをサーバーへ向け、証明書発行前に外部から 80 番ポートへ到達できることを確認します。

GitHub の `production` Environment を作成し、必要なら required reviewers を設定します。変数は `DEPLOY_HOST`、`DEPLOY_USER`、`DEPLOY_PORT`、`DEPLOY_PATH`、`IMAGE_NAME`、Secrets は `TS_OAUTH_CLIENT_ID`、`TS_AUDIENCE`、`DEPLOY_SSH_PRIVATE_KEY`、`DEPLOY_KNOWN_HOSTS` を設定します。Tailscale は Actions からの管理用 SSH 接続だけに使い、公開経路には使いません。

サーバーでデプロイ先を作り、`.env` を `deploy.env.example` から作成します。`APP_URL=https://motolotz.com` と `APP_DOMAIN=motolotz.com`、`LETSENCRYPT_EMAIL` は証明書通知先に設定し、`APP_KEY`、DB パスワード、YOLP Client ID などの機密情報はサーバー上だけに保管します。

```bash
sudo install -d -o <deploy-user> -g <deploy-user> /opt/motolotz
chmod 700 /opt/motolotz
```

## NginxとTLS

ホストOSのNginxで `motolotz.com` と `www.motolotz.com` の80/443番を待受し、`127.0.0.1:8000` へproxyします。Let’s Encrypt証明書はホストOS上のCertbotで発行・更新します。Docker Composeには80/443を公開するサービスを置かないため、Dockerの公開ポートがUFWルールを迂回する問題を回避できます。

Actions の **CI/CD** で `main` を選び、`本番環境へデプロイする` を有効にして実行します。

更新後は `https://<APP_DOMAIN>/up`、会員登録・ログイン・検索・詳細・登録・画像表示を手動確認します。Issue には対象コミット、日時、実施者、結果を記録します。

証明書更新はホストOSの `certbot.timer` を有効にして管理します。

## 運用

ログは各コンテナでローテーションされます。確認は `docker compose -f compose.deploy.yml logs --tail=200 app worker scheduler` と `journalctl -u nginx` を使います。障害通知先は監視サービスで設定し、通知先と担当者をIssueへ記録します。

毎日、MySQL の論理バックアップと `app-storage` ボリュームをサーバー外へ暗号化して保存します。保持期間と保存先を決め、初回リリース前に別環境で復元手順を検証してください。マイグレーションは前方互換にし、アプリイメージのロールバックではDBを自動で戻さない点に注意します。以前のdigestへ戻す場合は、停止前にバックアップを取り、`IMAGE_NAME` と `IMAGE_DIGEST` を指定して `scripts/deploy.sh` を実行します。
