FROM node:24-bookworm-slim AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY app/styles/ app/styles/
COPY app/scripts/ app/scripts/
RUN npm run build

FROM dunglas/frankenphp:1.12.0-php8.5-bookworm AS runtime
ENV PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d:/etc/php/conf.d
RUN --mount=type=bind,source=.devcontainer/common/etc/php/extensions.list,target=/tmp/extensions.list \
    tr -d '\r' < /tmp/extensions.list | xargs install-php-extensions \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY .devcontainer/common/ /
COPY .devcontainer/prod/ /
RUN mkdir -p /tmp/php-sessions /config /data \
    && chown -R 1000:1000 /tmp/php-sessions /config /data \
    && chmod 700 /tmp/php-sessions

FROM runtime AS application
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer* ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts
COPY . .
COPY --from=assets /app/public/ ./public/
RUN composer dump-autoload --optimize --no-dev \
    && composer build \
    && rm -rf app/styles app/scripts .devcontainer tests vite.config.js package.json \
    package-lock.json composer.json composer.lock .htaccess

FROM runtime AS production
COPY --from=application --chown=1000:1000 /app/ /app/
ENV XDG_CONFIG_HOME=/config \
    XDG_DATA_HOME=/data \
    SERVER_NAME=:80
USER 1000:1000
WORKDIR /app
EXPOSE 80 443 443/udp
HEALTHCHECK --interval=30s --timeout=15s --start-period=90s --retries=3 CMD ["php", "app/ice", "check", "health", "--port=8001"]
ENTRYPOINT ["frankenphp"]
CMD ["run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
