# Usamos una imagen oficial de PHP con Apache
FROM php:8.2-apache

# Actualizamos el sistema e instalamos las librerías necesarias para PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Habilitar mod_rewrite de Apache por si tu proyecto usa URLs amigables
RUN a2enmod rewrite

# Copiar el código fuente al directorio web de Apache
COPY . /var/www/html/

# Dar permisos adecuados
RUN chown -R www-data:www-data /var/www/html