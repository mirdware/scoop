FROM node:18-alpine AS node
WORKDIR /app
COPY . .
RUN npm install && npm start

FROM webdevops/php-apache:8.0-alpine
WORKDIR /app
COPY --from=node /app/public/ ./public/
COPY . .
COPY --chown=application:application . .
RUN composer install --optimize-autoloader --no-dev &&\
app/ice dbup &&\
rm gulpfile.js &&\
rm composer* &&\
rm package-lock.json
