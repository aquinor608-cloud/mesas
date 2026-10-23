FROM php:8.2-apache

# Instalar extensiones de PDO y MySQL para PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copiar el código del proyecto al servidor Apache
COPY . /var/www/html/

# Dar permisos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80