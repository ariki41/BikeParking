#!/usr/bin/env bash
set -Eeuo pipefail

deploy_path="${1:?Deployment path is required.}"
: "${IMAGE_NAME:?IMAGE_NAME is required.}"
: "${IMAGE_DIGEST:?IMAGE_DIGEST is required.}"

cd "$deploy_path"

if [[ ! -f .env ]]; then
    echo "Missing $deploy_path/.env. Copy deploy.env.example and configure it on the server." >&2
    exit 1
fi

export IMAGE_NAME IMAGE_DIGEST

docker compose -f compose.deploy.yml pull app
docker compose -f compose.deploy.yml up -d --wait mysql
docker compose -f compose.deploy.yml run --rm --no-deps app php artisan migrate --force
docker compose -f compose.deploy.yml up -d --wait app
docker compose -f compose.deploy.yml ps
