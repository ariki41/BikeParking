# KAGOYA Cloud 本番デプロイ

本番はGitHub Container Registry（GHCR）上のDockerイメージを、GitHub Actionsから `/opt/motolotz` に配置したDocker Compose構成として稼働させます。`main` へのマージでイメージのビルドとデプロイが自動実行されます。

## サーバー準備

Ubuntu x86_64に Docker Engine、Docker Compose plugin、Nginx、Certbotを導入します。デプロイユーザーがDockerを実行できるようにし、`/opt/motolotz/.env` は600権限でGit管理しません。

```bash
sudo install -d -o <deploy-user> -g <deploy-user> /opt/motolotz
sudo install -m 600 -o <deploy-user> -g <deploy-user> /dev/null /opt/motolotz/.env
```

`.env` には `APP_ENV=production`、`APP_DEBUG=false`、`APP_URL=https://motolotz.com`、DB接続情報、`APP_KEY`、YOLP Client IDを設定します。GitHubの`production` Environmentには `DEPLOY_HOST`、`DEPLOY_USER`、`DEPLOY_PORT`、`DEPLOY_SSH_PRIVATE_KEY`、`DEPLOY_KNOWN_HOSTS` を設定します。配置先はワークフローで `/opt/motolotz` に固定されています。

## 公開とTLS

UFWは80/443と管理元限定SSHだけを許可します。Nginx設定 [motolotz.com.conf](../deploy/nginx/motolotz.com.conf) を `/etc/nginx/sites-available/` へ配置して有効化します。Nginxはホストの `127.0.0.1:8000` で待ち受けるアプリコンテナへリバースプロキシします。

DNSのA/AAAAレコードをKAGOYAサーバーへ向けた後、HTTP設定を有効にしてCertbotを実行します。

```bash
sudo ln -s /etc/nginx/sites-available/motolotz.com.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d motolotz.com -d www.motolotz.com
sudo certbot renew --dry-run
sudo systemctl enable --now certbot.timer
```

キューとスケジューラはDocker Composeの `worker`・`scheduler` サービスとして常駐します。SupervisorやPHP-FPMの追加設定は不要です。

## リリースと運用

PRでは **CI** が品質チェック・テスト・アセットビルドを実行します。`main`へのマージ後、**Deploy production** がGHCRイメージを取得してComposeサービスを更新し、マイグレーションと `/up` のヘルスチェックを実行します。必要時は同ワークフローを `main` から手動実行して再デプロイできます。

毎日、MySQL Docker volumeの論理バックアップと`app-storage` volumeを暗号化してサーバー外へ保存し、初回リリース前に復元を検証します。マイグレーションは前方互換にします。Issue #170はHTTPS、主要機能、バックアップ復元、監視を確認してからクローズします。
