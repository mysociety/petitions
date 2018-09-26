#!/bin/sh

export DEBIAN_FRONTEND=noninteractive

echo -n "deb http://debian.mysociety.org wheezy main" | sudo tee /etc/apt/sources.list.d/mysociety.list
curl -s https://debian.mysociety.org/debian.mysociety.org.gpg.key | sudo apt-key add -
sudo apt-get update

sudo apt-get install -y postgresql

sudo xargs -a /home/vagrant/petitions/conf/packages apt-get install -y

# Create a "vagrant" PSQL user, to save us switching to "postgres" user
sudo -u postgres createuser --superuser $USER 2>/dev/null
createdb $USER -O $USER
# And let us log in as the "pet" (PSQL) user
echo 'localhost:5432:*:pet:password' > ~/.pgpass
chmod 600 ~/.pgpass

# Create "pet" PSQL user for the app to use
psql -c "CREATE USER pet WITH PASSWORD 'password';"
createdb "pet" -O "pet"

# Load data
psql -h "localhost" -U "pet" "pet" < /home/vagrant/petitions/db/schema.sql

# Set up config file
cp /home/vagrant/petitions/conf/general-example /home/vagrant/petitions/conf/general
sed -i "s/define[(]'OPTION_PET_DB_PASS', ''[)];/define('OPTION_PET_DB_PASS', 'password');/g" /home/vagrant/petitions/conf/general
sed -i "s/define[(]'OPTION_PET_STAGING', 0[)];/define('OPTION_PET_STAGING', 1);/g" /home/vagrant/petitions/conf/general
sed -i "s/define[(]'OPTION_PHP_DEBUG_LEVEL', 0[)];/define('OPTION_PHP_DEBUG_LEVEL', 1);/g" /home/vagrant/petitions/conf/general

# Wonder whether we should overwrite some of these settings too?
# define('OPTION_BASE_URL', 'http://www.---.com');
# define('OPTION_ADMIN_URL', 'https://secure.---.com');
# define('OPTION_ADMIN_PUBLIC', 0);
# define('OPTION_PHP_DEBUG_LEVEL', 0);

# Annoying that Petitions doesn't appear to support multiple cobrands at the same time.
# define('OPTION_SITE_NAME', 'sbdc');
# define('OPTION_SITE_PETITIONED', '');

sudo ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load
sudo cp /home/vagrant/petitions/conf/httpd.conf-vagrant /etc/apache2/sites-available/petitions.conf

sudo a2dissite 000-default
sudo a2ensite petitions.conf
sudo service apache2 reload
