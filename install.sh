#!/bin/bash

sudo apt-get -y update 1> /tmp/01.out 2>/tmp/01.err
sudo apt-get -y install git unzip apache2 php5 php5-curl php5-cli curl php5-gd php5-json

sudo service apache2 restart

wget -P /tmp https://github.com/srubioso/544-fall2013/archive/master.zip
sudo unzip -d /tmp/git /tmp/master 1>/tmp03.out 2>/tmp/03.err
sudo mv /tmp/git/544-fall2013-master/* /var/www/
sudo mv /var/www/composer.json /

curl -sS https://getcomposer.org/installer | php 1> /tmp/02.out 2> /tmp/02.err


sudo php composer.phar install 1>/tmp/04.out 2>/tmp/04.err

rm /var/www/index.html
mv /vendor /var/www 1>/tmp/05.out 2>/tmp/05.err

wget -P /tmp https://s3.amazonaws.com/custom-config/custom-config.php
mv /tmp/custom-config.php /var/www/vendor/aws/aws-sdk-php/src/Aws/Common/Resources/
