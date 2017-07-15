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
ln -s /etc/php/7.0/mods-available/pcs.ini /etc/php/7.0/fpm/conf.d/20-pcs.ini
ln -s /etc/php/7.0/mods-available/pcs.ini /etc/php/7.0/cli/conf.d/20-pcs.ini
echo "extension=igbinary.so" > /etc/php/7.0/mods-available/igbinary.ini
ln -s /etc/php/7.0/mods-available/igbinary.ini /etc/php/7.0/fpm/conf.d/20-igbinary.ini
ln -s /etc/php/7.0/mods-available/igbinary.ini /etc/php/7.0/cli/conf.d/20-igbinary.ini
echo "extension=couchbase.so" > /etc/php/7.0/mods-available/couchbase.ini
ln -s /etc/php/7.0/mods-available/couchbase.ini /etc/php/7.0/fpm/conf.d/30-couchbase.ini
ln -s /etc/php/7.0/mods-available/couchbase.ini /etc/php/7.0/cli/conf.d/30-couchbase.ini

service php7.0-fpm restart
