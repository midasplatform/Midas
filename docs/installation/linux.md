!!! note
    These instructions assume a clean 64-bit server installation of the
    applicable Linux operating system.

# Ubuntu 14.04 LTS (Trusty) #

## TL;DR ##

!!! important
    Substitute the name of the virtual host for "example.org" in these
    instructions.

These steps install and configure the requirements for development and a
superset of the requirements for production:

```bash
sudo apt-get update
sudo apt-get dist-upgrade
sudo apt-get install apache2 cmake curl git libapache2-mod-php5 memcached mysql-server nano php5 php5-curl php5-gd php5-json php5-memcached php5-mysqlnd php5-pgsql php5-sqlite php5-xdebug postgresql python python-jinja2 python-markdown python-pip python-yaml sqlite3 subversion
sudo pip install mkdocs
sudo a2enmod rewrite
echo -e "<Directory /var/www/example.org/html>\nAllowOverride All\n</Directory>\n<VirtualHost *:80>\nServerAdmin webmaster@example.org\nServerName example.org\nServerAlias www.example.org\nDocumentRoot /var/www/example.org/html\nErrorLog \${APACHE_LOG_DIR}/error.log\nCustomLog \${APACHE_LOG_DIR}/access.log combined\n</VirtualHost>" | sudo tee -a /etc/apache2/sites-available/example.org.conf
sudo a2ensite example.org.conf
sudo service apache2 restart
```

## Package Manager and Tools ##

Update the package database and upgrade all the packages on the system to the
latest version:

```bash
sudo apt-get update
sudo apt-get dist-upgrade
```

Install the cURL tool and the Nano editor:

```bash
sudo apt-get install curl nano
```

## Web Server ##

Install the Apache 2.4 web server:

```bash
sudo apt-get install apache2
```

Enable the mod_rewrite Apache module:

```bash
sudo a2enmod rewrite
```

Install the PHP 5.6 preprocessor and the mod_php5 Apache module:

```bash
sudo apt-get install libapache2-mod-php5 php5
```

Install the GD and JSON PHP extensions:

```bash
sudo apt-get install php5-gd php5-json
```

!!! important
    Substitute the name of the virtual host for "example.org" in these
    instructions.

Create a directory to hold the files of the virtual host:

```bash
sudo mkdir -p /var/www/example.org/html
sudo chown $USER:$USER /var/www/example.org/html
```

!!! note
    The value of $DOCUMENT_ROOT in the general instructions will be
    /var/www/example.org/html.

Add the configuration of the virtual host, including a Directory section
to allow apache to execute directives defined in .htaccess files:

```bash
sudo nano /etc/apache2/sites-available/example.org.conf

<Directory /var/www/example.org/html>
     AllowOverride All
</Directory>

<VirtualHost *:80>
    ServerAdmin webmaster@example.org
    ServerName example.org
    ServerAlias www.example.org
    DocumentRoot /var/www/example.org/html
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

Add the virtual host to your HOSTS file if the host name is not otherwise
resolvable to your system:

```bash
echo "127.0.0.1 example.org" | sudo tee -a /etc/hosts
```

Enable the virtual host:

```bash
sudo a2ensite example.org.conf
```

Restart the web server to load the additional PHP extensions and configuration:

```bash
sudo service apache2 restart
```

## Memory Caching System ##

!!! note
    For development, the memory caching system is optional.

Install the Memcached 1.4 memory caching system:

```bash
sudo apt-get install memcached
```

Install the Memcached PHP extension:

```bash
sudo apt-get install php5-memcached
```

Restart the web server to load the additional PHP extension:

```bash
sudo service apache2 restart
```

## Database Server ##

!!! note
    For production, install one of the database servers; for development,
    install all three.

### MySQL ###

Install the MySQL 5.5 database server:

```bash
sudo apt-get install mysql-server
```

Install the PDO_MYSQL PHP extension:

```bash
sudo apt-get install php5-mysqlnd
```

This also installs the MySQL native driver for PHP.

Restart the web server to load the additional PHP extensions:

```bash
sudo service apache2 restart
```

### PostgreSQL ###

Install the PostgreSQL 9.3 database server:

```bash
sudo apt-get install postgresql
```

Install the PDO_PGSQL PHP extension:

```bash
sudo apt-get install php5-pgsql
```

This also installs the PostgreSQL PHP extension.

Restart the web server to load the additional PHP extensions:

```bash
sudo service apache2 restart
```

### SQLite ###

Install the SQLite 3.8 database program:

```bash
sudo apt-get install sqlite3
```

Install the PDO_SQLITE PHP extension:

```bash
sudo apt-get install php5-sqlite
```

This also installs the SQLite PHP extension.

Restart the web server to load the additional PHP extensions:

```bash
sudo service apache2 restart
```

## Development Tools ##

!!! note
    For production, the development tools are not required.

Install the CMake 2.8 cross-platform make system, Git 1.9 distributed version
control system, and Subversion 1.8 version control system:

```bash
sudo apt-get install cmake git subversion
```

Install the cURL and Xdebug PHP extensions:

```bash
sudo apt-get install php5-curl php5-xdebug
```

Restart the web server to load the additional PHP extensions:

```bash
sudo service apache2 restart
```

Install the MkDocs Python package and its dependencies:

```bash
sudo apt-get install python python-jinja2 python-markdown python-pip python-yaml
sudo pip install mkdocs
```

# Fedora 21 #

## TL;DR ##

!!! important
    Substitute the name of the virtual host for "example.org" in these
    instructions.

These steps install and configure the requirements for development and a
superset of the requirements for production:

```bash
sudo yum update
sudo yum install cmake curl git httpd mariadb-server memcached nano php php-bcmath php-gd php-mysqlnd php-pdo php-pecl-jsonc php-pecl-memcached php-pecl-xdebug php-pgsql postgresql-server python python-jinja2 python-markdown python-pip python-watchdog PyYAML sqlite subversion
sudo pip install mkdocs
sudo mkdir -p /var/www/example.org/html
sudo chown $USER:$USER /var/www/example.org/html
echo -e "<VirtualHost *:80>\nServerAdmin webmaster@example.org\nServerName example.org\nServerAlias www.example.org\nDocumentRoot /var/www/example.org/html\nErrorLog \${APACHE_LOG_DIR}/error.log\nCustomLog \${APACHE_LOG_DIR}/access.log combined\n</VirtualHost>" | sudo tee -a /etc/httpd/conf.d/example.org.conf
sudo postgresql-setup initdb
sudo systemctl enable httpd.service
sudo systemctl enable mariadb.service
sudo systemctl enable memcached.service
sudo systemctl enable postgresql.service
sudo systemctl start httpd.service
sudo systemctl start memcached.service
sudo systemctl start mysqld.service
sudo systemctl start postgresql.service
```

## Package Manager and Tools ##

Update the package database and upgrade all the packages on the system to the
latest version:

```bash
sudo yum update
```

Install the cURL tool and the Nano editor:

```bash
sudo yum install curl nano
```

## Web Server ##

Install the Apache 2.4 web server:

```bash
sudo yum install httpd
```

The mod_rewrite Apache module is enabled by default.

Install the PHP 5.6 preprocessor:

```bash
sudo yum install php
```

The mod_php5 Apache module is installed along with the PHP preprocessor.

Install the BC Math, GD, and JSON-C PHP extensions:

```bash
sudo yum install php-bcmath php-gd php-pecl-jsonc
```

!!! important
    Substitute the name of the virtual host for "example.org" in these
    instructions.

Create a directory to hold the files of the virtual host:

```bash
sudo mkdir -p /var/www/example.org/html
sudo chown $USER:$USER /var/www/example.org/html
```

!!! note
    The value of $DOCUMENT_ROOT in the general instructions will be
    /var/www/example.org/html.

Add the configuration of the virtual host:

```bash
sudo nano /etc/httpd/conf.d/example.org.conf

<VirtualHost *:80>
    ServerAdmin webmaster@example.org
    ServerName example.org
    ServerAlias www.example.org
    DocumentRoot /var/www/example.org/html
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

Add the virtual host to your HOSTS file if the host name is not otherwise
resolvable to your system:

```bash
echo "127.0.0.1 example.org" | sudo tee -a /etc/hosts
```

Enable and start the web server:

```bash
sudo systemctl enable httpd.service
sudo systemctl start httpd.service
```

## Memory Caching System ##

!!! note
    For development, the memory caching system is optional.

Install the Memcached 1.4 memory caching system:

```bash
sudo yum install memcached
```

Enable and start the memory caching system:

```bash
sudo systemctl enable memcached.service
sudo systemctl start memcached.service
```

Install the Memcached PHP extension:

```bash
sudo yum install php-pecl-memcached
```

Restart the web server to load the additional PHP extension:

```bash
sudo systemctl restart httpd.service
```

## Database Server ##

!!! note
    For production, install one of the database servers; for development,
    install all three.

### MariaDB ###

Install the MariaDB 10.0 database server:

```bash
sudo yum install mariadb-server
```

MariaDB is a fork of MySQL.

Enable and start the MariaDB database server:

```bash
sudo systemctl enable mariadb.service
sudo systemctl start mariadb.service
```

Install the PDO_MYSQL PHP extension:

```bash
sudo yum install php-mysqlnd
```

This also installs the MySQL native driver for PHP.

Restart the web server to load the additional PHP extensions:

```bash
sudo systemctl restart httpd.service
```

### PostgreSQL ###

Install the PostgreSQL 9.4 database server:

```bash
sudo yum install postgresql-server
```

Initialize the PostgreSQL database:

```bash
sudo postgresql-setup initdb
```

Enable and start the PostgreSQL database server:

```bash
sudo systemctl enable postgresql.service
sudo systemctl start postgresql.service
```

Install the PDO_PGSQL PHP extension:

```bash
sudo yum install php-pgsql
```

This also installs the PostgreSQL PHP extension.

Restart the web server to load the additional PHP extensions:

```bash
sudo systemctl restart httpd.service
```

### SQLite ###

Install the SQLite 3.8 database program:

```bash
sudo yum install sqlite
```

Install the PDO_SQLITE PHP extension:

```bash
sudo yum install php-pdo
```

Restart the web server to load the additional PHP extension:

```bash
sudo systemctl restart httpd.service
```

## Development Tools ##

!!! note
    For production, the development tools are not required.

Install the CMake 3.0 cross-platform make system, Git 2.1 distributed version
control system, and Subversion 1.8 version control system:

```bash
sudo yum install cmake git subversion
```

Install the Xdebug PHP extension:

```bash
sudo yum install php-pecl-xdebug
```

The cURL PHP extension was previously installed along with the PHP
preprocessor.

Restart the web server to load the additional PHP extension:

```bash
sudo systemctl restart httpd.service
```

Install the MkDocs Python package and its dependencies:

```bash
sudo yum install python python-jinja2 python-markdown python-pip python-watchdog PyYAML
sudo pip install mkdocs
```

# Centos 7.0 and Red Hat Enterprise Linux 7.0 #

!!! important
    These instructions enable the Extra Packages for Enterprise Linux
    repository.

## TL;DR ##

!!! important
    Substitute the name of the virtual host for "example.org" in these
    instructions.

These steps install and configure the requirements for development and a
superset of the requirements for production:

```bash
sudo yum install epel-release
sudo yum update
sudo yum install cmake curl git httpd mariadb-server memcached nano php php-bcmath php-gd php-mysqlnd php-pdo php-pecl-memcached php-pecl-xdebug php-pgsql postgresql-server python python-jinja2 python-markdown python-pip PyYAML sqlite subversion
sudo pip install mkdocs
sudo mkdir -p /var/www/example.org/html
sudo chown $USER:$USER /var/www/example.org/html
echo -e "<VirtualHost *:80>\nServerAdmin webmaster@example.org\nServerName example.org\nServerAlias www.example.org\nDocumentRoot /var/www/example.org/html\nErrorLog \${APACHE_LOG_DIR}/error.log\nCustomLog \${APACHE_LOG_DIR}/access.log combined\n</VirtualHost>" | sudo tee -a /etc/httpd/conf.d/example.org.conf
sudo postgresql-setup initdb
sudo systemctl enable httpd.service
sudo systemctl enable mariadb.service
sudo systemctl enable memcached.service
sudo systemctl enable postgresql.service
sudo systemctl start httpd.service
sudo systemctl start mariadb.service
sudo systemctl start memcached.service
sudo systemctl start postgresql.service
```

## Package Manager and Tools ##

Enable the Extra Packages for Enterprise Linux repository:

```bash
sudo yum install epel-release
```

Update the package database and upgrade all the packages on the system to the
latest version:

```bash
sudo yum update
```

Install the cURL tool and the Nano editor:

```bash
sudo yum install curl nano
```

## Web Server ##

Install the Apache 2.4 web server:

```bash
sudo yum install httpd
```

The mod_rewrite Apache module is enabled by default.

Install the PHP 5.4 preprocessor:

```bash
sudo yum install php
```

The mod_php5 Apache module is installed along with the PHP preprocessor.

Install the BC Math and GD PHP extensions:

```bash
sudo yum install php-bcmath php-gd
```

The JSON PHP extension was previously installed along with the PHP
preprocessor.

!!! important
    Substitute the name of the virtual host for "example.org" in these
    instructions.

Create a directory to hold the files of the virtual host:

```bash
sudo mkdir -p /var/www/example.org/html
sudo chown $USER:$USER /var/www/example.org/html
```

!!! note
    The value of $DOCUMENT_ROOT in the general instructions will be
    /var/www/example.org/html.

Add the configuration of the virtual host:

```bash
sudo nano /etc/httpd/conf.d/example.org.conf

<VirtualHost *:80>
    ServerAdmin webmaster@example.org
    ServerName example.org
    ServerAlias www.example.org
    DocumentRoot /var/www/example.org/html
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

Add the virtual host to your HOSTS file if the host name is not otherwise
resolvable to your system:

```bash
echo "127.0.0.1 example.org" | sudo tee -a /etc/hosts
```

Enable and start the web server:

```bash
sudo systemctl enable httpd.service
sudo systemctl start httpd.service
```

## Memory Caching System ##

!!! note
    For development, the memory caching system is optional.

Install the Memcached 1.4 memory caching system:

```bash
sudo yum install memcached
```

Enable and start the memory caching system:

```bash
sudo systemctl enable memcached.service
sudo systemctl start memcached.service
```

Install the Memcached PHP extension:

```bash
sudo yum install php-pecl-memcached
```

Restart the web server to load the additional PHP extension:

```bash
sudo systemctl restart httpd.service
```

## Database Server ##

!!! note
    For production, install one of the database servers; for development,
    install all three.

### MariaDB ###

Install the MariaDB 5.5 database server:

```bash
sudo yum install mariadb-server
```

MariaDB is a fork of MySQL.

Enable and start the MariaDB database server:

```bash
sudo systemctl enable mariadb.service
sudo systemctl start mariadb.service
```

Install the PDO_MYSQL PHP extension:

```bash
sudo yum install php-mysqlnd
```

This also installs the MySQL native driver for PHP.

Restart the web server to load the additional PHP extensions:

```bash
sudo systemctl restart httpd.service
```

### PostgreSQL ###

Install the PostgreSQL 9.2 database server:

```bash
sudo yum install postgresql-server
```

Initialize the PostgreSQL database:

```bash
sudo postgresql-setup initdb
```

Enable and start the PostgreSQL database server:

```bash
sudo systemctl enable postgresql.service
sudo systemctl start postgresql.service
```

Install the PDO_PGSQL PHP extension:

```bash
sudo yum install php-pgsql
```

This also installs the PostgreSQL PHP extension.

Restart the web server to load the additional PHP extensions:

```bash
sudo systemctl restart httpd.service
```

### SQLite ###

Install the SQLite 3.7 database program:

```bash
sudo yum install sqlite
```

Install the PDO_SQLITE PHP extension:

```bash
sudo yum install php-pdo
```

Restart the web server to load the additional PHP extension:

```bash
sudo systemctl restart httpd.service
```

## Development Tools ##

!!! note
    For production, the development tools are not required.

Install the CMake 2.8 cross-platform make system, Git 1.8 distributed version
control system, and Subversion 1.7 version control system:

```bash
sudo yum install cmake git subversion
```

Install the Xdebug PHP extension:

```bash
sudo yum install php-pecl-xdebug
```

The cURL PHP extension was previously installed along with the PHP
preprocessor.

Restart the web server to load the additional PHP extension:

```bash
sudo systemctl restart httpd.service
```

Install the MkDocs Python package and its dependencies:

```bash
sudo yum install python python-jinja2 python-markdown python-pip PyYAML
sudo pip install mkdocs
```
