FROM        debian

# UPGRADE
RUN DEBIAN_FRONTEND=noninteractive apt-get update && \
	DEBIAN_FRONTEND=noninteractive apt-get upgrade -y && \
	DEBIAN_FRONTEND=noninteractive apt-get install -y wget curl locales gnupg

#NEW RELIC
RUN wget -O - https://download.newrelic.com/548C16BF.gpg | apt-key add - && \
    echo "deb http://apt.newrelic.com/debian/ newrelic non-free" > /etc/apt/sources.list.d/newrelic.list


# TIMEZONE
RUN echo "Europe/Paris" > /etc/timezone && \
	dpkg-reconfigure -f noninteractive tzdata
RUN export LANGUAGE=fr_FR.UTF-8 && \
	export LANG=fr_FR.UTF-8 && \
	export LC_ALL=fr_FR.UTF-8 && \
	locale-gen fr_FR.UTF-8 && \
	DEBIAN_FRONTEND=noninteractive dpkg-reconfigure locales

#UPDATE Ddebian repository for PHP 7.1
RUN apt install apt-transport-https ca-certificates \
	&& wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg \
	&& sh -c 'echo "deb https://packages.sury.org/php/ stretch main" > /etc/apt/sources.list.d/php.list'

# INSTALL PHP
#RUN apt-get update; apt-get install -y sudo php7.0 php7.0-cli php7.0-gd php7.0-imap php7.0-mbstring php7.0-xml php7.0-curl \
#    php7.0-mcrypt php7.0-zip php7.0-mysqlnd mysql-client libapache2-mod-php7.0 git

RUN apt-get update; apt-get install -y sudo php7.1 php7.1-cli php7.1-gd php7.1-imap php7.1-mbstring php7.1-xml php7.1-curl \
   php7.1-mcrypt php7.1-zip php7.1-mysqlnd mysql-client libapache2-mod-php7.1 git

# Let's set the default timezone in both cli and apache configs
#RUN sed -i 's/\;date\.timezone\ \=/date\.timezone\ \=\ Europe\/Paris/g' /etc/php/7.0/cli/php.ini
#RUN sed -i 's/\;date\.timezone\ \=/date\.timezone\ \=\ Europe\/Paris/g' /etc/php/7.0/apache2/php.ini
RUN sed -i 's/\;date\.timezone\ \=/date\.timezone\ \=\ Europe\/Paris/g' /etc/php/7.1/cli/php.ini
RUN sed -i 's/\;date\.timezone\ \=/date\.timezone\ \=\ Europe\/Paris/g' /etc/php/7.1/apache2/php.ini
#RUN sed -i 's/\;cgi\.fix_pathinfo\=1/cgi\.fix_pathinfo\=0/g' /etc/php/7.0/fpm/php.ini
#RUN sed -i 's/listen\ \=\ \/run\/php\/php7\.0\-fpm\.sock/listen\ \= 127\.0\.0\.1:9000/g' /etc/php/7.0/fpm/pool.d/www.conf


RUN DEBIAN_FRONTEND=noninteractive apt-get -yq install newrelic-php5

#APACHE
RUN a2enmod rewrite expires headers

#APACHE ENV
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid
ENV APACHE_RUN_DIR /var/run/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_SERVERADMIN cyril@jomaker.fr
ENV APACHE_SERVERNAME localhost
ENV APACHE_SERVERALIAS docker.localhost
ENV APACHE_DOCUMENTROOT /var/www


#COMPOSER
RUN curl -o /tmp/composer-setup.php https://getcomposer.org/installer \
  && curl -o /tmp/composer-setup.sig https://composer.github.io/installer.sig \
  && php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }" \
  &&  php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

#USER
RUN useradd -m -g www-data -G sudo -N -s /bin/bash jobmaker

#PHP CS FIXER
RUN curl -L http://cs.sensiolabs.org/download/php-cs-fixer-v2.phar -o php-cs-fixer \
  && chmod a+x php-cs-fixer &&  mv php-cs-fixer /usr/local/bin/php-cs-fixer


ADD docker/status.conf /etc/apache2/mods-available/status.conf
ADD docker/jobmaker.conf /etc/apache2/sites-available/000-default.conf
COPY docker/newrelic.ini /etc/php/7.0/mods-available/newrelic.ini

RUN   echo "ServerTokens Prod" >> /etc/apache2/apache2.conf
RUN   echo "ServerSignature Off" >> /etc/apache2/apache2.conf

EXPOSE 80
VOLUME ["/var/log/apache2"]
ENTRYPOINT ["/usr/sbin/apache2", "-D", "FOREGROUND"]


#docker build -t jobmaker .
#docker run -d -P -v /var/www/jobmaker:/var/www/jobmaker jobmaker
