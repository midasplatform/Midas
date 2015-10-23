# Midas Server #

![Midas Server](https://raw.githubusercontent.com/midasplatform/Midas/master/core/public/images/midas-200.png)

_Server component of the Midas Platform_

[![Build Status](https://img.shields.io/travis/midasplatform/Midas/master.svg)](https://travis-ci.org/midasplatform/Midas)
[![Coverage Status](https://img.shields.io/coveralls/midasplatform/Midas.svg)](https://coveralls.io/r/midasplatform/Midas)
[![Documentation Status](https://readthedocs.org/projects/midas-server/badge/?version=latest)](https://readthedocs.org/projects/midas-server/?badge=latest)
[![Code Climate](https://img.shields.io/codeclimate/github/midasplatform/Midas.svg)](https://codeclimate.com/github/midasplatform/Midas)
[![Packagist Version](https://img.shields.io/packagist/v/midas-platform/midas-server.svg)](https://packagist.org/packages/midas-platform/midas-server)
[![Packagist License](https://img.shields.io/packagist/l/midas-platform/midas-server.svg)](https://packagist.org/packages/midas-platform/midas-server)
[![PHP Dependencies](https://www.versioneye.com/user/projects/54fdf4ad4a10649b1b000002/badge.svg)](https://www.versioneye.com/user/projects/54fdf4ad4a10649b1b000002)
[![StyleCI](https://styleci.io/repos/18849230/shield)](https://styleci.io/repos/18849230)

## Overview ##

Midas Server is an open-source application that enables the rapid creation of
tailored, web-enabled data storage. Designed to meet the needs of advanced
data-centric computing, Midas Server addresses the growing challenge of large
data by providing a flexible, intelligent data storage system. Midas Server
integrates multimedia server technology with other open-source data analysis
and visualization tools to enable data-intensive applications that easily
interface with existing workflows.

<http://www.midasplatform.org/>

## Installation ##

```bash
git clone https://github.com/midasplatform/Midas.git midas
cd midas
chmod a+w core/configs/ data/ log/ tmp/
curl -sS https://getcomposer.org/installer | php
```

For development (PHP version 5.4.0 or above):

```bash
php composer.phar install
```

For production (PHP version 5.3.9 or above):

```bash
php composer.phar install --no-dev --optimize-autoloader
```

Full installation documentation is available at

<https://midas-server.readthedocs.org/>

## License ##

Midas Server is licensed under the Apache License, Version 2.0.
