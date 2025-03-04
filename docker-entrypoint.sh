#!/bin/bash
set -e

MAX_WAIT=30
WAIT_INTERVAL=2
ELAPSED=0

# Temporary cache driver override
export CACHE_DRIVER=array
export SESSION_DRIVER=array

check_postgres() {
    PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1" > /dev/null 2>&1
}

parse_database_url() {
    local URL=$1
    local PATTERN='^postgres(ql)?://([^:]+):([^@]+)@([^:]+):([0-9]+)/(.+)$'

    if [[ $URL =~ $PATTERN ]]; then
        export DB_USERNAME=${BASH_REMATCH[2]}
        export DB_PASSWORD=${BASH_REMATCH[3]}
        export DB_HOST=${BASH_REMATCH[4]}
        export DB_PORT=${BASH_REMATCH[5]}
        export DB_DATABASE=${BASH_REMATCH[6]}
        export DB_CONNECTION=pgsql
    else
        echo "Invalid DATABASE_URL format"
        exit 1
    fi
}

# Use DATABASE_URL if provided
if [ -n "$DATABASE_URL" ]; then
    parse_database_url "$DATABASE_URL"
fi

# Database connection check
if [[ -n "$DB_HOST" && -n "$DB_DATABASE" ]]; then
    echo "Waiting for PostgreSQL at $DB_HOST..."
    while ! check_postgres; do
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

    # Restore cache driver
    unset CACHE_DRIVER
    unset SESSION_DRIVER
fi

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
