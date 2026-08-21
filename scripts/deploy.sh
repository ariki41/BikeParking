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
docker compose -f compose.deploy.yml run --rm --no-deps app sh -ceu '
    if [ "${SEED_DEVELOPMENT_DATA:-false}" = "true" ]; then
        php artisan db:seed --force --class=Database\\Seeders\\DevelopmentSeeder
    else
        echo "Development seed data is disabled."
    fi
'

previous_image=''
if previous_container_id="$(docker compose -f compose.deploy.yml ps -q app 2>/dev/null)"; then
    if [[ -n "$previous_container_id" ]]; then
        previous_image="$(docker inspect --format '{{.Config.Image}}' "$previous_container_id")"
    fi
fi

if ! docker compose -f compose.deploy.yml up -d --wait app; then
    echo 'The application did not become healthy. Reverting the application container.' >&2

    if [[ "$previous_image" == *@* ]]; then
        previous_image_name="${previous_image%@*}"
        previous_image_digest="${previous_image#*@}"

        IMAGE_NAME="$previous_image_name" IMAGE_DIGEST="$previous_image_digest" \
            docker compose -f compose.deploy.yml up -d --wait app
        echo "Rollback completed: $previous_image" >&2
    else
        docker compose -f compose.deploy.yml stop app
        echo 'No previous application image was found; the unhealthy application container was stopped.' >&2
    fi

    exit 1
fi

echo "Deployment completed: $IMAGE_NAME@$IMAGE_DIGEST"
docker compose -f compose.deploy.yml ps
