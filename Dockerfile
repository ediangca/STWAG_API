# FROM php:8.2-fpm

# # Install system dependencies
# RUN apt-get update && apt-get install -y \
#     git \
#     curl \
#     libpng-dev \
#     libonig-dev \
#     libxml2-dev \
#     zip \
#     unzip \
#     libzip-dev \
#     libpq-dev \
#     default-mysql-client \
#     && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip

# # Set working directory
# WORKDIR /var/www

# # Copy project files
# COPY . /var/www

# # Install Composer
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# # Install PHP dependencies
# RUN composer install --no-dev --optimize-autoloader

# EXPOSE 10000

# CMD ["sh", "./start.sh"]



# ---------------------------------------------------------
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libonig-dev libxml2-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libpq-dev libssl-dev libmcrypt-dev default-libmysqlclient-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy Laravel project files
COPY . .

# Copy start.sh script into the container
COPY start.sh /start.sh

# Make the script executable
RUN chmod +x /start.sh

# Install PHP dependencies
# RUN composer install
# RUN npm install 

# RUN composer install --no-dev --optimize-autoloader \
#  && chown -R www-data:www-data /var/www \
#  && chmod -R 775 storage bootstrap/cache

# Install PHP dependencies
RUN composer install

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Install frontend dependencies
RUN npm install

# Expose the port your Laravel app will use
EXPOSE 42604

# Start Laravel server
# CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
# ---------------------------------------------------------
# while true; do php artisan spin:run; sleep 60; done
# CMD ["php", "while", "do", "php", "artisan", "spin:run;", "sleep", "60;", "done"]
# CMD ["sh", "-c", "while true; do php artisan spin:run; sleep 60; done"]

# Start the container using your custom start script
CMD ["/start.sh"]

