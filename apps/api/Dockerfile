# Imagen de la API para Render (Root Directory: apps/api, Runtime: Docker)
FROM php:8.3-apache

# mod_rewrite para que .htaccess mande todo a index.php
RUN a2enmod rewrite \
 && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

COPY . /var/www/html/

# Render indica el puerto en la variable PORT
ENV PORT=10000
CMD sed -i "s/Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf \
 && sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf \
 && apache2-foreground
