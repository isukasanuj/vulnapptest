#!/bin/sh
set -e

# The uploads dir is a named volume that mounts over the image path as root.
# Make it writable by the Apache user before starting.
mkdir -p /var/www/html/storage/uploads
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

exec apache2-foreground
