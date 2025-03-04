#!/bin/bash
set -e

MAX_WAIT=30
WAIT_INTERVAL=2
ELAPSED=0

# Temporary cache driver override
export CACHE_DRIVER=array
export SESSION_DRIVER=array

# Database connection check
if [[ -n "$DB_HOST" && -n "$DB_DATABASE" ]]; then
    echo "Waiting for PostgreSQL at $DB_HOST..."
    while ! PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1" > /dev/null 2>&1; do
        if [ "$ELAPSED" -ge "$MAX_WAIT" ]; then
            echo "PostgreSQL not ready after $MAX_WAIT seconds"
            exit 1
        fi
        echo "Waiting for PostgreSQL... (${ELAPSED}s)"
        sleep "$WAIT_INTERVAL"
        ELAPSED=$((ELAPSED + WAIT_INTERVAL))
    done
    echo "PostgreSQL connection established"

    # Generate app key if missing
    if [ -z "$APP_KEY" ]; then
        echo "Generating application key..."
        php artisan key:generate --force
    fi

    # Run database setup
    echo "Running migrations..."
    php artisan migrate --force

    # Create cache table if needed
    if ! php artisan tinker --execute='echo Schema::hasTable("cache") ? 1 : 0;' | grep -q 1; then
        echo "Creating cache table..."
        php artisan cache:table
        php artisan migrate --force
    fi
fi

# Production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restore cache driver
unset CACHE_DRIVER
unset SESSION_DRIVER

exec "$@"
