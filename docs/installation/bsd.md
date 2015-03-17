!!! note
    These instructions assume a clean 64-bit server installation of the
    applicable BSD operating system.

# FreeBSD 10.1 #

## TL;DR ##

!!! important
    Substitute the name of the virtual host for "example.org" in these
    instructions.

These steps install and configure the requirements for development and a
superset of the requirements for production:

```bash
sudo pkg update
sudo pkg upgrade
sudo pkg install apache24 cmake curl git memcached mod_php56 mysql56-server nano pecl-memcached php-xdebug php56 php56-curl php56-gd php56-json php56-pdo_mysql php56-pdo_pgsql php56-pdo_sqlite postgresql93-server py27-jinja2 py27-markdown py27-pip py27-watchdog py27-yaml python27 sqlite3 subversion
sudo pip install mkdocs
sudo sed -i '' '/^#LoadModule rewrite_module/s/^#//' /usr/local/etc/apache24/httpd.conf
sudo mkdir -p /usr/local/www/apache24/example.org/data
sudo chown $USER:$USER /usr/local/www/apache24/example.org/data
echo -e "<VirtualHost *:80>\nServerAdmin webmaster@example.org\nServerName example.org\nServerAlias www.example.org\nDocumentRoot /usr/local/www/apache24/example.org/data\nErrorLog \${APACHE_LOG_DIR}/error.log\nCustomLog \${APACHE_LOG_DIR}/access.log combined\n</VirtualHost>" | sudo tee -a /usr/local/etc/apache24/Includes/example.org.conf
echo "apache24_enable=\"yes\"" | sudo tee -a /etc/rc.conf
echo "memcached_enable=\"yes\"" | sudo tee -a /etc/rc.conf
echo "mysql_enable=\"yes\"" | sudo tee -a /etc/rc.conf
echo "postgresql_enable=\"yes\"" | sudo tee -a /etc/rc.conf
sudo service apache24 start
sudo service memcached start
sudo service mysql-server start
sudo service postgresql initdb
sudo service postgresql start
```

## Package Manager and Tools ##

Update the package database and upgrade all the packages on the system to the
latest version:

```bash
sudo pkg update
sudo pkg upgrade
```

Install the cURL tool and the Nano editor:

```bash
sudo pkg install curl nano
```

## Web Server ##

Install the Apache 2.4 web server:

```bash
sudo pkg install apache24
```

Enable the mod_rewrite Apache module:

```bash
sudo sed -i '' '/^#LoadModule rewrite_module/s/^#//' /usr/local/etc/apache24/httpd.conf
```

Install the PHP 5.6 preprocessor and the mod_php5 Apache module:

```bash
sudo pkg install mod_php56 php56
```

Install the GD and JSON PHP extensions:

```bash
sudo pkg install php56-gd php56-json
```

!!! important
    Substitute the name of the virtual host for "example.org" in these
    instructions.

Create a directory to hold the files of the virtual host:

```bash
sudo mkdir -p /usr/local/www/apache24/example.org/data
sudo chown $USER:$USER /usr/local/www/apache24/example.org/data
```

!!! note
    The value of $DOCUMENT_ROOT in the general instructions will be
    /usr/local/www/apache24/example.org/data.

Add the configuration of the virtual host:

```bash
sudo nano /usr/local/etc/apache24/Includes/example.org.conf

<VirtualHost *:80>
    ServerAdmin webmaster@example.org
    ServerName example.org
    ServerAlias www.example.org
    DocumentRoot /usr/local/www/apache24/example.org/data
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
echo "apache24_enable=\"yes\"" | sudo tee -a /etc/rc.conf
sudo service apache24 start
```

## Memory Caching System ##

!!! note
    For development, the memory caching system is optional.

Install the Memcached memory caching system:

```bash
sudo pkg install memcached
```

Enable and start the memory caching system:

```bash
echo "memcached_enable=\"yes\"" | sudo tee -a /etc/rc.conf
sudo service memcached start
```

Install the Memcached PHP extension:

```bash
sudo pkg install pecl-memcached
```

Restart the web server to load the additional PHP extension:

```bash
sudo service apache24 restart
```

## Database Server ##

!!! note
    For production, install one of the database servers; for development,
    install all three.

### MySQL ###

Install the MySQL 5.6 database server:

```bash
sudo pkg install mysql56-server
```

Enable and start the MySQL database server:

```bash
echo "mysql_enable=\"yes\"" | sudo tee -a /etc/rc.conf
sudo service mysql-server start
```

Install the PDO_MYSQL PHP extension:

```bash
sudo pkg install php56-pdo_mysql
```

Restart the web server to load the additional PHP extension:

```bash
sudo service apache24 restart
```

### PostgreSQL ###

Install the PostgreSQL 9.3 database server:

```bash
sudo pkg install postgresql93-server
```

Enable the PostgreSQL database server:

```bash
echo "postgresql_enable=\"yes\"" | sudo tee -a /etc/rc.conf
```

Initialize the PostgreSQL database:

```bash
sudo service postgresql initdb
```

Start the PostgreSQL database server:

```bash
sudo service postgresql start
```

Install the PDO_PGSQL PHP extension:

```bash
sudo pkg install php56-pdo_pgsql
```

Restart the web server to load the additional PHP extension:

```bash
sudo service apache24 restart
```

### SQLite ###

Install the SQLite database program:

```bash
sudo pkg install sqlite3
```

Install the PDO_SQLITE PHP extension:

```bash
sudo pkg install php56-pdo_sqlite
```

Restart the web server to load the additional PHP extension:

```bash
sudo service apache24 restart
```

## Development Tools ##

!!! note
    For production, the development tools are not required.

Install the CMake cross-platform make system, Git distributed version control
system, and Subversion version control system:

```bash
sudo pkg install cmake git subversion
```

Install the Xdebug and cURL PHP extensions:

```bash
sudo pkg install php-xdebug php56-curl
```

Restart the web server to load the additional PHP extensions:

```bash
sudo service apache24 restart
```

Install the MkDocs Python package and its dependencies:

```bash
sudo pkg install python27 py27-jinja2 py27-markdown py27-pip py27-watchdog py27-yaml
sudo pip install mkdocs
```
