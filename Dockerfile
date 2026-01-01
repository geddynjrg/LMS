FROM php:8.3-apache

# 1. Install system dependencies + required extensions for Laravel & Horizon
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    default-mysql-client \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        zip \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
    && a2enmod rewrite

# 2. Point Apache to the Laravel /public folder
# We overwrite the default Apache config with your custom one
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# 3. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Set working directory
WORKDIR /var/www/html

# 5. Copy project files into the image
COPY . /var/www/html

# 6. Install Laravel dependencies
# Increase the timeout to 300 seconds (5 minutes)
RUN COMPOSER_PROCESS_TIMEOUT=300 composer install --no-interaction --optimize-autoloader --no-dev
# 7. Set permissions so Laravel can write to logs and cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
