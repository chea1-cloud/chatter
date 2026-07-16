FROM php:8.2-cli

# Install any PHP extensions your app might need (like PDO or MySQLi)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy all your project files into the container
COPY . .

# Tell Docker to start PHP's built-in web server on 0.0.0.0 and bind to Railway's dynamic $PORT
CMD ["sh", "-c", "php -S 0.0.0.0:$PORT"]