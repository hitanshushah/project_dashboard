FROM php:8.2-fpm

# Install system dependencies and PHP extensions including xml (required by Laravel)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    libpq-dev && \
    docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd xml

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy all files
COPY . .

# Run composer install during image build
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Expose port and run php-fpm
EXPOSE 9000
CMD ["php-fpm"]