FROM php:8.2-fpm-alpine

# Installer les dépendances système
RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    curl \
    zip \
    unzip \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    supervisor

# Extensions PHP nécessaires pour Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    xml \
    bcmath \
    opcache \
    soap

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copier les fichiers de dépendances en premier (cache Docker)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copier le reste de l'application
COPY . .

# Générer la clé Laravel
RUN php artisan key:generate --no-interaction || true

# Optimiser Laravel pour la production
RUN php artisan config:cache --no-interaction || true
RUN php artisan route:cache --no-interaction || true
RUN php artisan view:cache --no-interaction || true

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configuration nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Configuration supervisord (gère nginx + php-fpm ensemble)
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
