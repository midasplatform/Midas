<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

define('MIDAS_DEMO_ENABLED_KEY', 'enabled');
define('MIDAS_DEMO_ENABLED_DEFAULT_VALUE', 1);

define('MIDAS_DEMO_ADMIN_EMAIL', 'admin@kitware.com');
define('MIDAS_DEMO_ADMIN_PASSWORD', 'admin');
define('MIDAS_DEMO_USER_EMAIL', 'user@kitware.com');
define('MIDAS_DEMO_USER_PASSWORD', 'user');

define('MIDAS_DEMO_DYNAMIC_HELP', '
  <b>To authenticate:</b><br /><br />
  Demo Administrator<br />
  - Login: '.MIDAS_DEMO_ADMIN_EMAIL.'<br />
  - Password: '.MIDAS_DEMO_ADMIN_PASSWORD.'<br /><br />
  Demo User<br />
  - Login: '.MIDAS_DEMO_USER_EMAIL.'<br />
  - Password: '.MIDAS_DEMO_USER_PASSWORD);
