#!/bin/bash

# Install this script to /usr/local/bin

DIR=./psc_backup
rm -rf $DIR
mkdir $DIR

mkdir -p $DIR/etc/profile.d
mkdir -p $DIR/etc/apache2/sites-enabled
# Create deepest child - things are copied into higher level folders in this hierarchy
mkdir -p $DIR/var/www/crt/ssl_cert

cp /etc/apache2/sites-enabled/petzshowcompanion.com.conf $DIR/etc/apache2/sites-enabled
zip -q -r $DIR/var/www/psc_data.zip /var/www/psc_data/*
mysqldump psc > $DIR/psc_db.sql
cp -r /var/www/crt/petzshowcompanion* $DIR/var/www/crt
cp -r /var/www/crt/ssl_cert/petzshowcompanion* $DIR/var/www/crt/ssl_cert
cp /etc/profile.d/petzshowcompanion.com.sh $DIR/etc/profile.d/petzshowcompanion.sh

NOW=$(date +"%m_%d_%Y")
zip -q -r ./psc_backup_$NOW.zip $DIR

YELLOW='\033[1;33m'
NC='\033[0m' # No Color
echo -e "${YELLOW}Backup to ./psc_backup_${NOW}.zip complete.${NC}\n"
if type tree > /dev/null; then
    tree ./psc_backup
    echo "\n"
fi

rm -rf $DIR
