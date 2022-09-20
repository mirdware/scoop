FROM node:18-alpine AS node
WORKDIR /app
COPY . .
RUN npm install && npm start

FROM webdevops/php-apache:8.0-alpine
WORKDIR /app
COPY . .
COPY --from=node /app/public/ ./public/
RUN rm gulpfile.js && composer install
