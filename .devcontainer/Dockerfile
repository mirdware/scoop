FROM node:22-alpine3.20 AS node
FROM webdevops/php-dev:8.4-alpine

COPY --from=node /usr/local/bin/ /usr/local/bin/
COPY --from=node /usr/local/lib/ /usr/local/lib/

COPY ./docker/  /opt/docker/
WORKDIR /app
EXPOSE 8000
