#!/bin/bash

echo -e "--------------------------------------------------"
echo -e "--- Update ---------------------------------------"
echo -e "--------------------------------------------------"

apt-get update

echo -e "--------------------------------------------------"
echo -e "--- Install PHP-CLI ------------------------------"
echo -e "--------------------------------------------------"

apt-get -y install php7.0-cli php7.0-curl php7.0-gd php7.0-intl php7.0-mysqlnd php7.0-mcrypt php7.0-mbstring php7.0-bcmath php7.0-zip php-xml

echo -e "--------------------------------------------------"
echo -e "--- Install CouchBase ----------------------------"
echo -e "--------------------------------------------------"

apt-get -y install python

wget https://packages.couchbase.com/releases/4.6.2/couchbase-server-enterprise_4.6.2-ubuntu14.04_amd64.deb
dpkg -i couchbase-server-enterprise_4.6.2-ubuntu14.04_amd64.deb
rm -f couchbase-server-enterprise_4.6.2-ubuntu14.04_amd64.deb

wget http://packages.couchbase.com/releases/couchbase-release/couchbase-release-1.0-2-amd64.deb
dpkg -i couchbase-release-1.0-2-amd64.deb
rm couchbase-release-1.0-2-amd64.deb
apt-get -y update
apt-get -y install libcouchbase-dev build-essential php-dev zlib1g-dev
pecl install pcs-1.3.3
pecl install igbinary
pecl install couchbase
echo "extension=pcs.so" > /etc/php/7.0/mods-available/pcs.ini
ln -s /etc/php/7.0/mods-available/pcs.ini /etc/php/7.0/cli/conf.d/20-pcs.ini
echo "extension=igbinary.so" > /etc/php/7.0/mods-available/igbinary.ini
ln -s /etc/php/7.0/mods-available/igbinary.ini /etc/php/7.0/cli/conf.d/20-igbinary.ini
echo "extension=couchbase.so" > /etc/php/7.0/mods-available/couchbase.ini
ln -s /etc/php/7.0/mods-available/couchbase.ini /etc/php/7.0/cli/conf.d/30-couchbase.ini
/opt/couchbase/bin/couchbase-cli cluster-init -c 127.0.0.1:8091 --cluster-init-username=Administrator --cluster-init-password=Administrator --cluster-init-port=8091 --cluster-init-ramsize=2048 --cluster-index-ramsize=512 --services=data,index,query,fts
/opt/couchbase/bin/couchbase-cli bucket-create -c 127.0.0.1:8091 --bucket=yii2test --bucket-type=couchbase --bucket-port=11211 --bucket-ramsize=256 -u Administrator -p Administrator
/opt/couchbase/bin/cbq -e http://127.0.0.1:8091 -u Administrator -p Administrator -s="CREATE PRIMARY INDEX ON \`yii2test\`"

echo -e "--------------------------------------------------"
echo -e "--- Install Git ----------------------------------"
echo -e "--------------------------------------------------"

apt-get -y install git

echo -e "--------------------------------------------------"
echo -e "--- Install Composer -----------------------------"
echo -e "--------------------------------------------------"

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin/ --filename=composer
php -r "unlink('composer-setup.php');"
runuser -l ubuntu -c 'composer global require "fxp/composer-asset-plugin:*"'

echo -e "--------------------------------------------------"
echo -e "--- PHPUnit --------------------------------------"
echo -e "--------------------------------------------------"

wget https://phar.phpunit.de/phpunit.phar
chmod +x phpunit.phar
sudo mv phpunit.phar /usr/local/bin/phpunit

echo -e "--------------------------------------------------"
echo -e "--- Clean up -------------------------------------"
echo -e "--------------------------------------------------"

apt-get -y autoremove
apt-get -y autoclean

exit 0