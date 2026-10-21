#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
  echo "APP_KEY is required." >&2
  exit 1
fi

php artisan migrate --force --seed
exec php artisan serve --host=0.0.0.0 --port=8000
