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
    gnupg \
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

# Install Node.js 18.x and NPM
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# 2. Set working directory
WORKDIR /var/www/html

# 3. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. OPTIMIZATION: Install PHP dependencies first
# This layer is cached unless composer.json/lock changes
COPY composer.json composer.lock ./
RUN COMPOSER_PROCESS_TIMEOUT=300 composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-dev \
    --prefer-dist

# 5. OPTIMIZATION: Install NPM dependencies
COPY package.json package-lock.json* ./
RUN npm install

# 6. Copy the rest of the application files
COPY . .

# 7. Final Build Steps (Now that files are present)
RUN composer dump-autoload --optimize --no-dev
RUN npm run build

# 8. Increase PHP memory limit for both Composer and the App
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini

# 9. Point Apache to the Laravel /public folder
# We overwrite the default Apache config with your custom one
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# 10. Set permissions so Laravel can write to logs and cache
RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

RUN git config --global --add safe.directory /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
