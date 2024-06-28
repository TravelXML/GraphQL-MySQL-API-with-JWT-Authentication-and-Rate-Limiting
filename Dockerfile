FROM php:8.0-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip mysqli \
    && pecl install redis \
    && docker-php-ext-enable redis

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www/html

# Copy composer.json and install dependencies
COPY composer.json ./


# Set the environment variable to allow running as root
ENV COMPOSER_ALLOW_SUPERUSER=1


# Copy the rest of the application files
COPY . .

# Set permissions for the entry point script
RUN chmod +x ./InstallComposerDependencies.sh

# Install any additional PHP extensions
RUN ./InstallComposerDependencies.sh

# Expose port 80
EXPOSE 80
