FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer.json and composer.lock
COPY composer*.json ./

# Copy existing application directory contents
COPY . /var/www

# Create .env file from .env.example
RUN cp .env.example .env

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Generate app key
RUN php artisan key:generate --force

# Set production environment
RUN sed -i 's/APP_ENV=local/APP_ENV=production/' .env
RUN sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env

# Cache configurations
RUN php artisan config:cache
RUN php artisan route:cache

# Add a script to wait for the database and run migrations
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 2000 (as specified in your .env)
EXPOSE 2000

# Start Laravel server with the entrypoint script
ENTRYPOINT ["docker-entrypoint.sh"]
CMD php artisan serve --host=0.0.0.0 --port=$PORT
