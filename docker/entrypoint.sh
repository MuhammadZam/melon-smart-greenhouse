#!/bin/sh
set -e

# Pastikan permission folder storage dan bootstrap cache aman
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# Jalankan command utama container
exec "$@"
