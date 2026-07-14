FROM php:8.2-apache

# PostgreSQL için gerekli olan sistem kütüphanelerini yüklüyoruz
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Apache mod_rewrite ve .htaccess için
RUN a2enmod rewrite

# Apache'nin ortam değişkenlerini PHP'ye aktarması için
RUN echo 'PassEnv DB_HOST DB_PORT DB_NAME DB_USER DB_PASS CORS_ORIGIN' >> /etc/apache2/conf-available/envvars.conf \
    && a2enconf envvars

# Kodlarımızı sunucu içine kopyalıyoruz
COPY . /var/www/html/

# Apache'nin belge kökünde index.php'yi çalıştır
WORKDIR /var/www/html/
