FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    icu-dev \
    libzip-dev \
    zip \
    unzip \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
    bcmath \
    gd \
    intl \
    pdo_mysql \
    zip \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Crear usuario no-root (auditoria M-02) para que PHP-FPM y el resto
# de procesos corran sin privilegios. uid/gid 1000 = primer usuario
# humano en la mayoria de hosts Linux, evita choques con bind mounts.
RUN addgroup -g 1000 -S clientflow && \
    adduser -u 1000 -S clientflow -G clientflow

WORKDIR /var/www/html

# Asegurar que el usuario puede escribir en storage, cache y bootstrap
# cache. El volumen de desarrollo monta el codigo desde fuera, asi que
# estos chown son idempotentes.
RUN chown -R clientflow:clientflow /var/www/html

USER clientflow
