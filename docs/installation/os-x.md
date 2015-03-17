!!! note
    These instructions assume a clean installation of the applicable OS X
    operating system with the latest version of the Command Line Tools for
    Xcode installed.

# OS X 10.10 Yosemite #

## TL;DR ##

!!! important
    Substitute the name of the virtual host for "example.org" in these
    instructions.

These steps install and configure the requirements for development and a
superset of the requirements for production:

```bash
ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
brew tap homebrew/php
brew update
brew upgrade
brew install cmake memcached mysql php56 php56-memcached php56-pdo-pgsql php56-xdebug postgresql
brew install libyaml --universal
sudo pip install mkdocs
echo /usr/local/opt/php56/bin | sudo tee -a /etc/paths.d/20-PHP56
sudo sed -i '' '/^#LoadModule rewrite_module/s/^#//' /etc/apache2/httpd.conf
echo -e "LoadModule php5_module /usr/local/opt/php55/libexec/apache2/libphp5.so\nAddType application/x-httpd-php .php\nDirectoryIndex index.html index.php" | sudo tee -a /etc/apache2/other/php5.conf
sudo mkdir -p /Library/WebServer/example.org/Documents
sudo chown $USER:staff /Library/WebServer/example.org/Documents
echo -e "<VirtualHost *:80>\nServerAdmin webmaster@example.org\nServerName example.org\nServerAlias www.example.org\nDocumentRoot /Library/WebServer/example.org/Documents\nErrorLog \${APACHE_LOG_DIR}/error.log\nCustomLog \${APACHE_LOG_DIR}/access.log combined\n</VirtualHost>" | sudo tee -a /etc/apache2/other/example.org.conf
ln -sf /usr/local/opt/memcached/*.plist ~/Library/LaunchAgents
ln -sf /usr/local/opt/mysql/*.plist ~/Library/LaunchAgents
ln -sf /usr/local/opt/postgresql/*.plist ~/Library/LaunchAgents
launchctl load ~/Library/LaunchAgents/homebrew.mxcl.memcached.plist
launchctl load ~/Library/LaunchAgents/homebrew.mxcl.mysql.plist
launchctl load ~/Library/LaunchAgents/homebrew.mxcl.postgresql.plist
sudo apachectl restart
```

## Package Manager and Tools ##

Install the Homebrew package manager:

```bash
ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
```

Enable the Homebrew PHP repository:

```bash
brew tap homebrew/php
```

Update the package database and upgrade all the packages on the system to the
latest version.

```bash
brew update
brew upgrade
```

The cURL tool and the Nano editor are installed on the system by default.

## Web Server ##

The Apache 2.4 web server is installed on the system by default.

Enable the mod_rewrite Apache module:

```bash
sudo sed -i '' '/^#LoadModule rewrite_module/s/^#//' /etc/apache2/httpd.conf
```

Install the PHP 5.6 preprocessor and the mod_php5 Apache module:

```bash
brew install php56
```

This also installs the GD and JSON PHP extensions.

Add the PHP preprocessor to the PATH:

```bash
echo /usr/local/opt/php56/bin | sudo tee -a /etc/paths.d/20-PHP56
```

Enable the mod_php Apache module:

```bash
sudo nano /etc/apache2/other/php5.conf

LoadModule php5_module /usr/local/opt/php55/libexec/apache2/libphp5.so
AddType application/x-httpd-php .php
DirectoryIndex index.html index.php
```

!!! important
    Substitute the name of the virtual host for "example.org" in these
    instructions.

Create a directory to hold the files of the virtual host:

```bash
sudo mkdir -p /Library/WebServer/example.org/Documents
sudo chown $USER:staff /Library/WebServer/example.org/Documents
```

!!! note
    The value of $DOCUMENT_ROOT in the general instructions will be
    /Library/WebServer/example.org/Documents.

Add the configuration of the virtual host:

```bash
sudo nano /etc/apache2/other/example.org.conf

<VirtualHost *:80>
    ServerAdmin webmaster@example.org
    ServerName example.org
    ServerAlias www.example.org
    DocumentRoot /Library/WebServer/example.org/Documents
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

Add the virtual host to your HOSTS file if the host name is not otherwise
resolvable to your system:

```bash
echo "127.0.0.1 example.org" | sudo tee -a /etc/hosts
```

Restart the web server to load the additional PHP extensions and configuration:

```bash
sudo apachectl restart
```

## Memory Caching System ##

!!! note
    For development, the memory caching system is optional.

Install the Memcached memory caching system:

```bash
brew install memcached
```

Enable and start the memory caching system:

```bash
ln -sf /usr/local/opt/memcached/*.plist ~/Library/LaunchAgents
launchctl load ~/Library/LaunchAgents/homebrew.mxcl.memcached.plist
```

Install the Memcached PHP extension:

```bash
brew install php56-memcached
```

Restart the web server to load the additional PHP extension:

```bash
sudo apachectl restart
```

## Database Server ##

!!! note
    For production, install one of the database servers; for development,
    install all three.

### MySQL ###

Install the MySQL database server:

```bash
brew install mysql
```

The PDO_MYSQL PHP extension was previously installed along with the PHP
preprocessor.

Enable and start the MySQL database server:

```bash
ln -sf /usr/local/opt/mysql/*.plist ~/Library/LaunchAgents
launchctl load ~/Library/LaunchAgents/homebrew.mxcl.mysql.plist
```

### PostgreSQL ###

Install the PostgreSQL 9.3 database server:

```bash
brew install postgresql
```

Enable and start the PostgreSQL database server:

```bash
ln -sf /usr/local/opt/postgresql/*.plist ~/Library/LaunchAgents
launchctl load ~/Library/LaunchAgents/homebrew.mxcl.postgresql.plist
```

Install the PDO_PGSQL PHP extension:

```bash
brew install php56-pdo-pgsql
```

Restart the web server to load the additional PHP extension:

```bash
sudo apachectl restart
```

### SQLite ###

The SQLite database program is installed on the system by default and the
PDO_SQLITE PHP extension was previously installed along with the PHP
preprocessor.

## Development Tools ##

!!! note
    For production, the development tools are not required.

Install the CMake cross-platform make system:

```bash
brew install cmake
```

The Git distributed version control system and Subversion version control
system are installed on the system as part of the Command Line Tools for Xcode.

Install the Xdebug PHP extension:

```bash
brew install php56-xdebug
```

The cURL PHP extension was previously installed along with the PHP
preprocessor.

Restart the web server to load the additional PHP extension:

```bash
sudo apachectl restart
```

Install the MkDocs Python package and its dependencies:

```bash
brew install libyaml --universal
sudo pip install mkdocs
```

Python 2.7 is installed on the system by default.
