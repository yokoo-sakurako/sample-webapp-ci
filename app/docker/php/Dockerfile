FROM php:8.2-apache

# 必要なパッケージとMySQL接続用の拡張モジュールをインストール
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && docker-php-ext-install pdo_mysql

# Apacheの mod_rewrite を有効化
RUN a2enmod rewrite

# ★【追加】Apacheの公開ディレクトリ（DocumentRoot）を src フォルダに変更
ENV APACHE_DOCUMENT_ROOT /var/www/html/src
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# テストツールなどを入れるためのComposerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ソースコード（app/配下の全ファイル）をコンテナにコピー
COPY . /var/www/html

# Apacheがファイルを読み込めるよう所有権を変更
RUN chown -R www-data:www-data /var/www/html
