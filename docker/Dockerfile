# ───── Stage 1: Composer ─────
FROM php:8.4-cli AS composer

# Install system dependencies and Composer
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    libicu-dev \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev

# Install required PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl zip gd

# Install Composer manually
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

WORKDIR /var/www/html

# Copy composer files only
COPY composer.json composer.lock ./

# Install PHP dependencies (no dev for production)
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader

# ───── Stage 2: Laravel App ─────
FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev \
    libssl-dev \
    pkg-config \
    zlib1g-dev \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_pgsql \
        zip \
        intl \
        gd

# Set working directory
WORKDIR /var/www/html

# Copy full Laravel source code
COPY . .

# Copy vendor from composer stage
COPY --from=composer /var/www/html/vendor ./vendor

# Optional: Ensure correct permissions (uncomment if needed)
# RUN chown -R www-data:www-data /var/www/html \
#     && chmod -R 755 /var/www/html

# Default command (defined via docker-compose entrypoint)
