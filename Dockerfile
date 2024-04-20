# Usa la imagen base de PHP
FROM php:8.3.4-fpm

# Establece la zona horaria a Europe/Madrid
RUN ln -snf /usr/share/zoneinfo/Europe/Madrid /etc/localtime && echo Europe/Madrid > /etc/timezone

# Actualiza los paquetes y luego instala dependencias necesarias
RUN apt-get update -y \
    && apt-get install -y \
    libonig-dev \
    openssl \
    git \
    libzip-dev \
    unzip \
    zip \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install zip \
    pdo \
    mbstring \
    && apt-get clean

# Establece la variable de entorno COMPOSER_ALLOW_SUPERUSER
ENV COMPOSER_ALLOW_SUPERUSER=1

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Establece el directorio de trabajo en el directorio del proyecto Laravel
# WORKDIR /app

# Copia los archivos del proyecto Laravel al directorio de trabajo
COPY . .

# Instala las dependencias de Composer
RUN composer install

# Exponer el puerto 9000 para conectar con nginx/uwsgi
EXPOSE 9432

# Ejecuta el servidor PHP-FPM
CMD php artisan serve --host=0.0.0.0 --port=9432
