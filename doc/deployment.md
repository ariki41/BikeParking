# KAGOYA Cloud 本番デプロイ

本番はDockerを使わず、GitHub Actionsがテスト済みソースを `/opt/motolotz/releases/<release-id>` へ配置します。`/opt/motolotz/current` を新リリースへ切り替えるため、コードのロールバックは以前のreleaseへのシンボリックリンク切替で行えます。

## サーバー準備

Ubuntu x86_64に PHP 8.5-FPM、Composer、Node.js 22、npm、MySQL 8、Nginx、Supervisor、Certbotを導入します。`/opt/motolotz/shared/.env` と `/opt/motolotz/shared/storage` はリリース間で共有し、`.env` は600権限でGit管理しません。

```bash
sudo install -d -o <deploy-user> -g <deploy-user> /opt/motolotz/{releases,shared/storage,scripts}
sudo install -m 600 -o <deploy-user> -g <deploy-user> /dev/null /opt/motolotz/shared/.env
```

`.env` には `APP_ENV=production`、`APP_DEBUG=false`、`APP_URL=https://motolotz.com`、DB接続情報、`APP_KEY`、YOLP Client IDを設定します。GitHubの`production` Environmentには `DEPLOY_HOST`、`DEPLOY_USER`、`DEPLOY_PORT`、`DEPLOY_PATH=/opt/motolotz` と `DEPLOY_SSH_PRIVATE_KEY`、`DEPLOY_KNOWN_HOSTS` を設定します。

## 公開とTLS

UFWは80/443と管理元限定SSHだけを許可します。Nginx設定 [motolotz.com.conf](../deploy/nginx/motolotz.com.conf) を `/etc/nginx/sites-available/` へ配置して有効化します。PHP-FPMはUnix socketを使うため、アプリのコンテナ公開ポートは不要です。

DNSのA/AAAAレコードをKAGOYAサーバーへ向けた後、HTTP設定を有効にしてCertbotを実行します。

```bash
sudo ln -s /etc/nginx/sites-available/motolotz.com.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d motolotz.com -d www.motolotz.com
sudo certbot renew --dry-run
sudo systemctl enable --now certbot.timer
```

Supervisor設定 [motolotz-worker.conf](../deploy/supervisor/motolotz-worker.conf) を `/etc/supervisor/conf.d/` へ配置し、`sudo supervisorctl reread && sudo supervisorctl update` を実行します。デプロイユーザーには、パスワードなしで `systemctl reload php8.5-fpm` と `supervisorctl` を実行できる限定sudo権限を与えます。

## リリースと運用

Actionsの **CI/CD** を`main`で手動実行し、`本番環境へデプロイする`を有効にします。サーバーではComposerとnpmによる依存導入・ビルド、キャッシュ生成、マイグレーション、`current`切替、PHP-FPM/ワーカー再読み込み、`/up`確認を実施します。

毎日、MySQL論理バックアップと`shared/storage`を暗号化してサーバー外へ保存し、初回リリース前に復元を検証します。マイグレーションは前方互換にします。Issue #170はHTTPS、主要機能、バックアップ復元、監視を確認してからクローズします。
