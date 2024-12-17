FROM node:20-alpine AS node
WORKDIR /app
COPY ["gulpfile.js", "jsconfig.json", "package*.json", "./"]
RUN npm install
COPY app/styles/. app/styles/
COPY app/scripts/. app/scripts/
RUN npm start

FROM webdevops/php-apache:8.2-alpine
WORKDIR /app
COPY ["composer*", "./"]
RUN composer install --optimize-autoloader --no-dev
COPY --from=node /app/public/ ./public/
COPY --chmod=0644 .devcontainer/etc/  /opt/docker/etc/
COPY --chown=application:application . .
RUN rm -rf \
app/styles \
app/scripts \
.devcontainer \
gulpfile.js \
jsconfig.json \
package-lock.json \
composer.lock
