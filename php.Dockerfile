FROM php:7.1-fpm

# Install dependencies we need
RUN apt-get update && apt-get install -y \
 bzip2 \
 exiftool \
 git-core \
 imagemagick \
 libbz2-dev \
 libc-client2007e-dev \
 libjpeg-dev \
 libkrb5-dev \
 libldap2-dev \
 libmagickwand-dev \
 libmcrypt-dev \
 libmemcached-dev \
 libpng12-dev \
 libpq-dev \
 libxml2-dev \
 libicu-dev \
 mysql-client \
 postgresql-client \
 pv \
 ssh \
 unzip \
 wget \
 xfonts-base \
 xfonts-75dpi \
 zlib1g-dev \
 && pecl install apcu \
 && pecl install imagick \
 && pecl install memcached \
 && pecl install oauth-2.0.2 \
 && pecl install redis-3.1.2 \
 && pecl install xdebug \
 && docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
 && docker-php-ext-configure imap --with-imap-ssl --with-kerberos \
 && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
 && docker-php-ext-enable apcu \
 && docker-php-ext-enable imagick \
 && docker-php-ext-enable memcached \
 && docker-php-ext-enable oauth \
 && docker-php-ext-enable redis \
 && docker-php-ext-enable xdebug \
 && docker-php-ext-install \
 bcmath \
 bz2 \
 calendar \
 exif \
 gd \
 imap \
 ldap \
 mcrypt \
 mbstring \
 mysqli \
 opcache \
 pdo \
 pdo_mysql \
 pdo_pgsql \
 soap \
 zip \
 intl \
 gettext \
 && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && php -r "unlink('composer-setup.php');" \
 && apt-get -y clean \
 && apt-get -y autoclean \
 && apt-get -y autoremove

RUN { \
	echo 'xdebug.remote_enable=on'; \
	echo 'xdebug.remote_autostart=on'; \
} > /usr/local/etc/php/conf.d/xdebug.ini 

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
	echo 'opcache.memory_consumption=128'; \
	echo 'opcache.interned_strings_buffer=8'; \
	echo 'opcache.max_accelerated_files=4000'; \
	echo 'opcache.revalidate_freq=60'; \
	echo 'opcache.fast_shutdown=1'; \
	echo 'opcache.enable_cli=1'; \
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini



# install drush and drupal console symlink so it can be accessed from anywhere in the container
RUN ln -s /var/www/html/vendor/drush/drush/drush /usr/local/bin/drush
RUN ln -s /var/www/html/vendor/drupal/console/bin/drupal /usr/local/bin/drupal

# when you run bash, this is where you'll start, and where drush will be able to run from.
WORKDIR /var/www/html/web