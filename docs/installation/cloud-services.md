!!! note
    These instructions assume a clean installation of the local operating
    system.

# Google App Engine 1.9.18 #

!!! important
    These instructions assume a paid Google App Engine account has already been
    setup.

## Production ##

!!! important
    The server must be configured locally before being uploaded to Google App
    Engine, so these instructions apply to the local operating system, unless
    stated otherwise.

### Ubuntu 14.04 LTS (Trusty) ###

Install the cURL tool and the Nano editor:

```bash
sudo apt-get update
sudo apt-get dist-upgrade
sudo apt-get install curl nano
```

Install the PHP 5.6 CGI and the GD and JSON PHP extensions:

```bash
sudo apt-get install php5-cgi php5-gd php5-json
```

Install Python 2.7:

```bash
sudo apt-get install python
```

### Fedora 21, Centos 7.0, and Red Hat Enterprise Linux 7.0 ###

Install the cURL tool and the Nano editor:

```bash
sudo yum update
sudo yum install curl nano
```

Install the PHP CLI and GD PHP extension:

```bash
sudo yum install php-cli php-gd
```

The JSON PHP extension is installed along with the PHP preprocessor.

Install Python 2.7:

```bash
sudo yum install python
```

### OS X 10.10 Yosemite ###

Install Xcode and/or the Command Line Tools for Xcode. A satisfactory version
of the PHP 5.5 CLI, with the GD and JSON PHP extensions, is installed on the
system by default. Python 2.7 is also installed on the system by default.

## Development ##

!!! important
    Begin by following the above instructions for production.

### Ubuntu 14.04 LTS (Trusty) ###

Install the cURL and Xdebug PHP extensions:

```bash
sudo apt-get install php5-curl php5-xdebug
```

Install the MySQL 5.5 database server:

```bash
sudo apt-get install mysql-server
```

Install the Git 1.9 distributed version and Subversion 1.8 version control
system:

```bash
sudo apt-get install git subversion
```

### Fedora 21, Centos 7.0, and Red Hat Enterprise Linux 7.0 ###

Install the Xdebug PHP extension:

```bash
sudo yum install php-pecl-xdebug
```

The cURL PHP extension was previously installed along with the PHP
preprocessor.

Install, enable, and start the MariaDB database server:

```bash
sudo yum install mariadb-server
sudo systemctl enable mariadb.service
sudo systemctl start mariadb.service
```

MariaDB is a fork of MySQL.

Install the Git distributed version control system and Subversion version
control system:

```bash
sudo yum install git subversion
```

### OS X 10.10 (Yosemite) ###

Install the Homebrew package manager, enable the Homebrew PHP repository, and
install the PHP 5.6 CGI.

```bash
ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
brew tap homebrew/php
brew update
brew upgrade
brew install php56
echo /usr/local/opt/php56/bin | sudo tee -a /etc/paths.d/20-PHP56
```

The GD and JSON PHP extensions are installed along with the PHP preprocessor.

Install the Xdebug PHP extension:

```bash
brew install php56-xdebug
```

The cURL PHP extension was previously installed along with the PHP
preprocessor.

Install, enable, and start the MySQL database server:

```bash
brew install mysql
ln -sf /usr/local/opt/mysql/*.plist ~/Library/LaunchAgents
launchctl load ~/Library/LaunchAgents/homebrew.mxcl.mysql.plist
```

The Git distributed version control system and Subversion version control
system are installed on the system as part of the Command Line Tools for Xcode.

## Production and Development ##

Download and install the [Google App Engine
SDK](https://cloud.google.com/appengine/downloads).

Create a new project in the [Google Developers
Console](https://console.developers.google.com/). The project name will also be
the application ID of the Google App Engine application.

Within the new project, create a new [Google Cloud
Storage](https://cloud.google.com/storage/docs/getting-started-console) bucket,
and a new [Google Cloud SQL](https://cloud.google.com/sql/docs/create-instance)
instance.

!!! important
    Ensure that the "preferred location" of the Cloud SQL instance is set to
    "specify App Engine application" and the project name is specified, and
    ensure that the project name is specified in the list of "authorized App
    Engine applications".
