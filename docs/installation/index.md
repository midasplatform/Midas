# System Dependencies #

Begin by following the appropriate operating-system-specific instructions:

* [Linux](linux.md)
* [OS X](os-x.md)
* [BSD](bsd.md)
* [Cloud Services](cloud-services.md)

# Installation and Configuration #

!!! important
    Substitute the document root of the virtual host for $DOCUMENT_ROOT and the
    latest release version for $LATEST_RELEASE in these instructions. If
    installing to a cloud service, choose any empty directory for the document
    root.

## TL;DR ##

```bash
curl -LsS https://github.com/midasplatform/Midas/archive/$LATEST_RELEASE.tar.gz -o Midas-$LATEST_RELEASE.tar.gz
tar cf Midas-$LATEST_RELEASE.tar.gz
cp -r Midas-$LATEST_RELEASE/. $DOCUMENT_ROOT
cd $DOCUMENT_ROOT
chmod a+w core/configs/ data/ log/ tmp/
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

## Download ##

Download the gzip compressed tar archive (tar.gz) of the [latest
release](https://github.com/midasplatform/Midas/releases/latest) from GitHub:

```bash
curl -LsS https://github.com/midasplatform/Midas/archive/$LATEST_RELEASE.tar.gz -o Midas-$LATEST_RELEASE.tar.gz
```

Extract the archive and copy its contents to the document root:

```bash
tar cf Midas-$LATEST_RELEASE.tar.gz
cp -r Midas-$LATEST_RELEASE/. $DOCUMENT_ROOT
```

## Directory Permissions ##

Change directory into the document root:

```bash
cd $DOCUMENT_ROOT
```

Ensure that the core configurations, data, log, and temporary folders are
writable:

```bash
chmod a+w core/configs/ data/ log/ tmp/
```

## Third-Party PHP Dependencies ##

Download and install the latest version of Composer:

```bash
curl -sS https://getcomposer.org/installer | php
```

### Production ###

Download and install the third-party PHP dependencies:

```bash
php composer.phar install --no-dev --prefer-dist
```

!!! important
    If applicable, now upload the contents of the document root to the cloud
    service.

### Development ###

Download and install the third-party PHP dependencies, including development
dependencies:

```bash
php composer.phar install
```

## Configuration ##

Open a browser, visit the address of the virtual host, and follow the
instructions on each page in turn. This will write the configuration files and
create the necessary database tables.
