#!/bin/sh

php artisan config:cache
# Run migrations
php artisan migrate --force


# Start Laravel server in the background
php artisan serve --host=0.0.0.0 --port=8080 &

# while true; do php artisan spin:run; sleep 60; done

# Run spinning algorithm every 60 seconds
while true; do
  php artisan spin:run
  sleep 60
done