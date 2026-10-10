FROM node:24-bookworm-slim AS node
FROM dunglas/frankenphp:1.12.0-php8.5-bookworm
ENV PHP_INI_SCAN_DIR="/usr/local/etc/php/conf.d:/etc/php/conf.d"
RUN --mount=type=bind,source=common/etc/php/extensions.list,target=/tmp/extensions.list \
    apt-get update && apt-get install -y --no-install-recommends git unzip curl \
    && rm -rf /var/lib/apt/lists/* \
    && tr -d '\r' < /tmp/extensions.list | xargs install-php-extensions xdebug \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY --from=node /usr/local/ /usr/local/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY common/ /
COPY dev/ /
WORKDIR /app
EXPOSE 8000 8001
HEALTHCHECK --interval=30s --timeout=15s --start-period=90s --retries=3 CMD ["php", "app/ice", "check", "health", "--port=8001"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
