<?php

if (function_exists('apache_get_modules')) {
  $modules = apache_get_modules();
  $mod_rewrite = in_array('mod_rewrite', $modules);
} else {
  $mod_rewrite =  getenv('HTTP_MOD_REWRITE')=='On' ? true : false ;
}

if(!$mod_rewrite)
  {
  echo "Please install/enable the module rewrite";exit;
  }
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'on');
set_include_path('.'
 . PATH_SEPARATOR . './library'
 . PATH_SEPARATOR . './application/dao/'
 . PATH_SEPARATOR . get_include_path());

define('BASE_PATH', realpath(dirname(__FILE__)));

define('START_TIME',microtime(true));

require_once 'Zend/Loader/Autoloader.php';
require_once 'Zend/Application.php';

$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('App_');

require_once(BASE_PATH . "/include.php");

// Create application, bootstrap, and run
$application = new Zend_Application('global', CORE_CONFIG);

$application->bootstrap()
->run();

