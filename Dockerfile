FROM node:22-alpine3.20 AS node
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY vite.config.js ./
COPY app/styles/. app/styles/
COPY app/scripts/. app/scripts/
RUN npm start

FROM webdevops/php-apache:8.4-alpine
WORKDIR /app
COPY composer* ./
RUN composer install --optimize-autoloader --no-dev
COPY --from=node /app/public/ ./public/
COPY --chmod=0644 .devcontainer/docker/  /opt/docker/
COPY --chown=application:application . .
RUN su application -c "composer build" && rm -rf \
app/styles \
app/scripts \
.devcontainer \
vite.config.js \
package.json \
package-lock.json \
composer.json \
composer.lock
