#!/bin/bash

# Install this script to /usr/local/bin

DIR=./psc_stage_refresh
rm -rf $DIR
mkdir $DIR

mysqldump psc > $DIR/psc_PROD_db.sql
mysql -e "drop database psc_stage; create database psc_stage"
sed -i '1s/^/use psc_stage;\n/' $DIR/psc_PROD_db.sql
mysql < $DIR/psc_PROD_db.sql
rm -rf /var/www/psc_stage_data/*
cp -R /var/www/psc_data/* /var/www/psc_stage_data
rm -rf $DIR
