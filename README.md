# Prerequisites
* NodeJS
* PHP 8.2+
* Composer
* MySQL 8+
* Apache 2.4+ (you're on your own for other web servers)


# Setup


### Apache
There is nothing special about the Apache configuration. Add a virtualhost (below) or run the site from the apache webroot.

```
<VirtualHost petzshowcompanion:80>
    DocumentRoot "d:\code\petzshowcompanion\public"
    ServerName petzshowcompanion
    ServerAlias petzshowcompanion
    ErrorLog "logs\petzshowcompanion-error.log"
    CustomLog "logs\petzshowcompanion-access.log" common

    <Directory "d:\code\petzshowcompanion\public">
        Options Indexes FollowSymLinks Includes
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Don't forget the corresponding hosts file entry (C:\Windows\System32\drivers\etc\hosts):
```
127.0.0.1 petzshowcompanion
```


### MySQL
Create a new database:
```
create database petzshowcompanion;
use petzshowcompanion;
```

Then, open schema\schema.sql and run it. It will set up the empty tables. If there are future updates to the database schema, running this script again will apply only the new changes.

**Note:** If your local MySQL server differs from default hostname and port settings you will need to update the DATABASE_URL variable in the .env file.


### PHP
Run `composer install` from the root directory.


### JavaScript
Run `npm install` from the root directory.

Run `npm run dev` to build the JavaScript assets.


### .env
You will need to update some variables in the .env file. First, add a value for APP_SECRET. You can generate a value to copy/paste from the command line using a PHP command:

```
php -r "echo md5(time());"
```

Also set an appropriate PIC_PATH (the directory where uploaded pet pics will be stored) as well as a LOG_FILE location.

Default configuration puts PIC_PATH outside of the directory where the web site is deployed to. This allows wiping and replacing the entire directory if needed while keeping the pics in a safe location. Because of this, you will need to add a symlink (shortcut) inside \public called "pics" which directs to the folder defined in PIC_PATH. You can create the appropriate symlink using a command such as this, ran from the root directory:
```
mklink /d .\public\pics D:\tmp\petzshowcompanion_pics
```

Finally, run the following to generate a local configuration file:
```
composer dump-env dev
```


### Environment Variables
You will need to create the following environment variables with proper values for your environment:
PSC_MYSQL_USER
PSC_MYSQL_PASSWORD


### User Setup
At this point you will be able to bring up http://petzshowcompanion in your browser and register a user. Due to the email verification requirement, you will need to bypass this by modifying the user record in the database.
```
update user set verified = 1 where id = 1;
```

You may also desire to give this user the Admin role:
```
update user set roles = '{"0": "ROLE_ADMIN"}' where id = 1;
```