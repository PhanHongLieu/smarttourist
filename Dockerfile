FROM php:8.1-apache

# Cài đặt extension PDO MySQL để kết nối TiDB/Aiven
RUN docker-php-ext-install pdo pdo_mysql

# Kích hoạt mod_rewrite cho file .htaccess
RUN a2enmod rewrite

# Cấu hình Apache trỏ vào thư mục public thay vì thư mục gốc
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy toàn bộ code vào container
COPY . /var/www/html/

# Cấp quyền cho thư mục
RUN chown -R www-data:www-data /var/www/html