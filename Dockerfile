FROM php:8.2-apache

# 1. Cài đặt PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# 2. Thay đổi DocumentRoot của Apache sang thư mục public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 3. Copy toàn bộ code vào container
COPY . /var/www/html/

# 4. Cấp quyền cho thư mục (để tránh lỗi ghi file)
RUN chown -R www-data:www-data /var/www/html

RUN a2enmod rewrite
EXPOSE 80