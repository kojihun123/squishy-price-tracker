FROM php:7.4-apache

# CodeIgniter 3 needs pdo_mysql/mysqli for DB access, and mod_rewrite for clean URLs
RUN docker-php-ext-install pdo_mysql mysqli \
    && a2enmod rewrite

# Allow .htaccess overrides (CI3 clean URLs)
RUN sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Document root is /var/www/html (default), where web/ is mounted
