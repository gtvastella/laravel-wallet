#!/bin/bash

if [ ! -f .env ]; then
    cp .env.example .env
fi

dockerize -wait tcp://db:3306 -timeout 200s -wait-retry-interval 7s
if ! php artisan migrate:status > /dev/null 2>&1; then
    php artisan migrate --force &&
    php artisan vendor:publish --tag=laravel-pt-br-localization &&
    php artisan db:seed --force
else
    php artisan migrate --force
fi

apache2-foreground
