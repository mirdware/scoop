FROM node:18-alpine AS node
WORKDIR /app
COPY ["gulpfile.js", "jsconfig.json", "package.json", "package-lock.json", "./"]
RUN npm install
COPY app/styles/. app/styles/
COPY app/scripts/. app/scripts/
RUN npm start

FROM webdevops/php-apache:8.0-alpine
WORKDIR /app
COPY .devcontainer/php.ini /opt/docker/etc/php/php.ini
COPY ["composer.json", "composer.lock", "./"]
RUN composer install --optimize-autoloader --no-dev
COPY --from=node /app/public/ ./public/
COPY --chown=application:application . .
RUN rm -rf \
app/styles \
app/scripts \
.devcontainer \
gulpfile.js \
jsconfig.json \
package.json \
package-lock.json \
composer.json \
composer.lock
