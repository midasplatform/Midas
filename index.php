<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

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

define('BASE_PATH', realpath(dirname(__FILE__)));

set_include_path('.'
  . PATH_SEPARATOR . './library'
  . PATH_SEPARATOR . './core/dao/'
  . PATH_SEPARATOR . get_include_path());

if(isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache'))
  {
  if(function_exists('apache_get_modules'))
    {
    $mod_rewrite = in_array('mod_rewrite', apache_get_modules());
    }
  else
    {
    $mod_rewrite = getenv('HTTP_MOD_REWRITE') == 'On' ? true : false;
    }

  if(!$mod_rewrite)
    {
    echo "Please install/enable the Apache rewrite module";
    exit();
    }
  }

define('START_TIME', microtime(true));

require_once 'Zend/Loader/Autoloader.php';
require_once 'Zend/Application.php';

$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('App_');

require_once(BASE_PATH . '/core/include.php');

if(!is_writable(LOCAL_CONFIGS_PATH))
  {
  echo '<p>To use Midas Platform, the folder "' . LOCAL_CONFIGS_PATH . '" must be writable by your web server.</p>';
  exit();
  }

$application = new Zend_Application('global', CORE_CONFIG);
$application->bootstrap()->run();
