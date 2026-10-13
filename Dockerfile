FROM php:8.2-apache

# Install SSL certificates and required PHP extensions for MySQL (PDO & MySQLi)
RUN apt-get update && apt-get install -y ca-certificates && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable output buffering in PHP configuration (matches XAMPP behavior to prevent headers already sent errors)
RUN echo "output_buffering = 4096" > /usr/local/etc/php/conf.d/output-buffering.ini

# Enable Apache mod_rewrite for clean URLs if needed
RUN a2enmod rewrite

# Copy application files to Apache root
COPY . /var/www/html/

# Configure uploads directory permissions
RUN mkdir -p /var/www/html/uploads/food \
             /var/www/html/uploads/logos \
             /var/www/html/uploads/licenses \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Configure Apache to listen on Render's dynamic PORT environment variable (default: 80)
ENV PORT=80
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

EXPOSE 80

CMD ["apache2-foreground"]
