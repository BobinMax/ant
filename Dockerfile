FROM php:7.2
COPY . /usr/src/myapp
WORKDIR /usr/src/myapp
CMD [ "php", "app" ]