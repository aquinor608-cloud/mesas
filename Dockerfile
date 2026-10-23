FROM php:8.2-apache

# Instalar extensión SQLite y PDO SQLite
RUN apt-get update && apt-get install -y libsqlite3-dev && docker-php-ext-install pdo_sqlite

# Copiar el código del proyecto
COPY . /var/www/html/

# Dar permisos completos a la carpeta web para que SQLite pueda guardar/modificar datos
RUN chown -R www-data:www-data /var/www/html && chmod -R 777 /var/www/html

EXPOSE 80