#!/bin/bash
set -e

MAX_WAIT=30
WAIT_INTERVAL=2
ELAPSED=0

# Function to check PostgreSQL connectivity
check_postgres() {
    PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1" > /dev/null 2>&1
}

# Parse DATABASE_URL if set
if [ -n "$DATABASE_URL" ]; then
    echo "Parsing DATABASE_URL..."
    DB_CONNECTION=$(echo "$DATABASE_URL" | sed -r 's|^[^:]+://([^:]+):([^@]+)@([^:/]+)(:[0-9]+)?/(.+)$|pgsql|')
    DB_USERNAME=$(echo "$DATABASE_URL" | sed -r 's|^[^:]+://([^:]+):([^@]+)@([^:/]+)(:[0-9]+)?/(.+)$|\1|')
    DB_PASSWORD=$(echo "$DATABASE_URL" | sed -r 's|^[^:]+://([^:]+):([^@]+)@([^:/]+)(:[0-9]+)?/(.+)$|\2|')
    DB_HOST=$(echo "$DATABASE_URL" | sed -r 's|^[^:]+://([^:]+):([^@]+)@([^:/]+)(:[0-9]+)?/(.+)$|\3|')
    DB_PORT=$(echo "$DATABASE_URL" | sed -r 's|^[^:]+://([^:]+):([^@]+)@([^:/]+):([0-9]+)?/(.+)$|\4|' | sed 's|^:||' || echo "5432")
    DB_DATABASE=$(echo "$DATABASE_URL" | sed -r 's|^[^:]+://([^:]+):([^@]+)@([^:/]+)(:[0-9]+)?/(.+)$|\5|')
fi

# Wait for PostgreSQL if DB vars are set
if [[ -n "$DB_HOST" && -n "$DB_DATABASE" ]]; then
    echo "Waiting for PostgreSQL at $DB_HOST to be ready..."
    while ! check_postgres; do
        if [ "$ELAPSED" -ge "$MAX_WAIT" ]; then
            echo "Error: PostgreSQL not ready after $MAX_WAIT seconds. Exiting."
            exit 1
        fi
        echo "PostgreSQL not ready yet, waiting..."
        sleep "$WAIT_INTERVAL"
        ELAPSED=$((ELAPSED + WAIT_INTERVAL))
    done
    echo "PostgreSQL connection established"

    # Run migrations
    php artisan migrate --force
    echo "Migrations completed"
else
    echo "No DATABASE_URL or DB_HOST/DB_DATABASE set. Skipping database checks."
fi

# Start the main process
exec "$@"
