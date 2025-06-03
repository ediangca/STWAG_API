#!/bin/sh

# Run migrations
php artisan migrate --force

# Run spinning algorithm every 60 seconds
while true; do
  php artisan spin:run
  sleep 60
done

# Start Laravel server in the background
php artisan serve --host=0.0.0.0 --port=42604 &

# while true; do php artisan spin:run; sleep 60; done
