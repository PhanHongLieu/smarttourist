FROM php:8.2-apache
# Copy toàn bộ code vào thư mục gốc của Apache
COPY . /var/www/html/
# Mở cổng 80
EXPOSE 80