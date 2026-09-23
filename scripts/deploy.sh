#!/usr/bin/env bash
set -Eeuo pipefail

deploy_path="${1:?Deployment path is required.}"
release_id="${2:?Release ID is required.}"
releases_path="$deploy_path/releases"
shared_path="$deploy_path/shared"
release_path="$releases_path/$release_id"
archive_path="$releases_path/$release_id.tar.gz"

test -f "$archive_path"
test -f "$shared_path/.env"
test -d "$shared_path/storage"
test ! -e "$release_path"

mkdir "$release_path"
trap 'rm -rf "$release_path" "$archive_path"' ERR
tar -xzf "$archive_path" -C "$release_path"
rm -f "$archive_path"

ln -s "$shared_path/.env" "$release_path/.env"
rm -rf "$release_path/storage"
ln -s "$shared_path/storage" "$release_path/storage"

cd "$release_path"
composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

previous_release="$(readlink -f "$deploy_path/current" || true)"
ln -sfn "$release_path" "$deploy_path/current"
sudo systemctl reload php8.5-fpm
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart motolotz-worker:*

if ! curl --fail --silent --max-time 10 http://127.0.0.1/up >/dev/null; then
    if [[ -n "$previous_release" ]]; then
        ln -sfn "$previous_release" "$deploy_path/current"
        sudo systemctl reload php8.5-fpm
        sudo supervisorctl restart motolotz-worker:*
    fi
    exit 1
fi
