FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql
RUN echo '<Directory /var/www/html/dofus>\n    AllowOverride All\n    Options -Indexes\n    Require all granted\n</Directory>' \
    > /etc/apache2/conf-available/starloco.conf \
    && a2enconf starloco
