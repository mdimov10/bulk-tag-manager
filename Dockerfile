# Use the official PHP image with required extensions
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    nginx \
    libzip-dev \
    libpq-dev \
    libcurl4-openssl-dev \
    mariadb-client \
    supervisor \
    gnupg \
    ca-certificates

# ------------------------
# Install Node.js 18 for Vite
# ------------------------
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
RUN apt-get install -y nodejs

# ------------------------
# Install PHP extensions
# ------------------------
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# ------------------------
# Install Composer
# ------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# ------------------------
# Copy only package files first to cache node_modules
# ------------------------
COPY package*.json ./

# Install frontend deps
RUN npm install

# ------------------------
# Copy all app files
# ------------------------
COPY . .

# ------------------------
# Install Laravel dependencies
# ------------------------
RUN composer install --no-dev --optimize-autoloader

# ------------------------
# Build frontend assets (Vite)
# ------------------------
RUN npm run build

# ------------------------
# Set file permissions
# ------------------------
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# ------------------------
# Add the custom entrypoint script
# ------------------------
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# ------------------------
# Start Laravel through the entrypoint
# ------------------------
CMD ["/entrypoint.sh"]
