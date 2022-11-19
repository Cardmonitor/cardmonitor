#!/bin/sh

echo "Deploying..."

git stash
git pull
mv .env.production .env
php artisan migrate --force
php artisan queue:restart
composer install  > /dev/null 2>&1 & echo $! #--no-interaction --no-dev --prefer-dist
npm install > /dev/null 2>&1 & echo $!
npm run production > /dev/null 2>&1 & echo $!
php artisan lang:generate --quiet

echo "Deployed"