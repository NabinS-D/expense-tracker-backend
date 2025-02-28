#!/bin/bash
set -e

# Convert PORT to integer if it's set
if [ -n "$PORT" ]; then
    export PORT=$(($PORT))
fi

# Run database migrations if needed
if [[ -n "$DB_HOST" && -n "$DB_DATABASE" ]]; then
    echo "Waiting for database to be ready..."

    # Check if we can connect to the PostgreSQL database
    # This will retry until the database is available
    while ! PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1" > /dev/null 2>&1; do
        echo "Database not ready yet, waiting..."
        sleep 2
    done

    echo "Database connection established"

    # Run migrations
    php artisan migrate --force

    echo "Migrations completed"
fi

# Execute the main command (CMD from Dockerfile)
exec "$@"
