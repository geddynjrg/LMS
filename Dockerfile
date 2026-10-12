FROM php:8.3-apache

# Install system dependencies + required extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
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
    redis-tools \
    tesseract-ocr \
    tesseract-ocr-eng \
    poppler-utils \
    libreoffice-calc-nogui \
    libreoffice-writer-nogui \
    fonts-dejavu-core \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        zip \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js 18.x and NPM (only if you need to run build inside container)
# If you keep Vite on host, you can comment this out to reduce image size.
# RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
#     && apt-get install -y nodejs

WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-plugins --no-scripts --no-dev --prefer-dist

# Copy the rest of the application (including .env if present)
COPY . .

# Generate app key (if .env doesn't have one, or we'll do it at runtime)
RUN php artisan key:generate --force || true

# Set permissions
RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    storage/app/incoming-loan-applications \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN git config --global --add safe.directory /var/www/html

# Increase memory limit
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Large JSON POST bodies from the loans portal (base64 file attachments)
COPY .docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Copy custom vhost (Laravel public directory)
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]
