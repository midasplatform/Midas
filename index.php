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

if(!is_writable(BASE_PATH."/application/configs")||!is_writable(BASE_PATH."/log")||!is_writable(BASE_PATH."/data")||!is_writable(BASE_PATH."/tmp"))
  {
  echo "To use Midas, the following repertories have to be writable by apache:
        <ul>
          <li>".BASE_PATH."/application/configs</li>
          <li>".BASE_PATH."/log</li>
          <li>".BASE_PATH."/data</li>
          <li>".BASE_PATH."/tmp</li>
        </ul>";
  exit();
  }

define('START_TIME',microtime(true));

require_once 'Zend/Loader/Autoloader.php';
require_once 'Zend/Application.php';

$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('App_');

require_once(BASE_PATH . "/application/include.php");

// Create application, bootstrap, and run
$application = new Zend_Application('global', CORE_CONFIG);

$application->bootstrap()
->run();

