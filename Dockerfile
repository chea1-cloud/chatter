FROM php:8.2-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy application files to the web server directory
COPY . /var/www/html/

# Set permissions so PHP can write to the database and uploads folders
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80