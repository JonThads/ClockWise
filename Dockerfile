# Use official PHP + Apache image
FROM php:8.1-apache

# Install mysqli extension for MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy app source into container
COPY ./src/ /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Expose port 80 for Apache
EXPOSE 80