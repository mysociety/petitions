#!/bin/sh

set -e

cd /vagrant/

if [ "$(locale -a | egrep -i "^en_GB.utf-?8$" | wc -l)" != "1" ]
then
    echo "Adding en_GB.UTF-8 locale..."
    echo en_GB.UTF-8 UTF-8 >> /etc/locale.gen
    locale-gen
    echo 'LANG="en_GB.UTF-8"' > /etc/default/locale
    echo 'LC_ALL="en_GB.UTF-8"' >> /etc/default/locale
    export LANG="en_GB.UTF-8"
    export LC_ALL="en_GB.UTF-8"
fi

# We need curl to fetch the apt key
apt-get update -qq
apt-get install -qq -y curl >/dev/null

echo -n "deb http://debian.mysociety.org wheezy main" > /etc/apt/sources.list.d/mysociety.list
curl -s https://debian.mysociety.org/debian.mysociety.org.gpg.key | apt-key add -
apt-get update -qq
sudo xargs -a conf/packages apt-get install -y
sudo xargs -a conf/vagrant-packages apt-get install -y

[ -e /etc/apache2/sites-enabled/petitions ] || ln -s /vagrant/conf/vagrant-vhost /etc/apache2/sites-enabled/petitions

if [ -e /etc/apache2/sites-enabled/000-default ]
then
    rm /etc/apache2/sites-enabled/000-default
fi

a2enmod rewrite
a2enmod fcgid

service apache2 restart

sudo -u postgres psql -c "CREATE USER pet WITH SUPERUSER PASSWORD 'somepassword'"
sudo -u postgres psql -c "CREATE DATABASE pet WITH OWNER pet"
sudo -u postgres psql -d pet -f /vagrant/db/schema.sql

[ -e conf/general ] || patch -i conf/general-example.vagrant-patch -o conf/general conf/general-example
chmod 0644 conf/general

echo "Petitions is up and running, you can view it at http://petitions.127.0.0.1.xip.io:8080/"