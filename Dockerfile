FROM php:8.0-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite if needed
RUN a2enmod rewrite

# Restart apache service
RUN service apache2 restart
