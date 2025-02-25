FROM php:7.4-apache

# Instalar extensiones PHP y dependencias
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite

# Copiar los archivos de la aplicación
COPY . /var/www/html/

# Configurar los permisos correctos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exponer el puerto 80
EXPOSE 80

# Comando para iniciar Apache
CMD ["apache2-foreground"]