#!/bin/sh

# 1. Wait for a second or two just to ensure the network handshake is ready
echo "Checking database connection and running migrations..."
php artisan migrate --force

# 2. Run your custom seeding and backfill routines
echo "Seeding collections and NFTs..."
php ./dev-tools/seed-collections-and-nfts.php

echo "Running backfill script..."
chmod +x ./dev-tools/backfill.sh
./dev-tools/backfill.sh

# 3. Hand over control to PHP-FPM
# This keeps the container alive and listening for Nginx requests
echo "Starting PHP-FPM application server..."
exec php-fpm