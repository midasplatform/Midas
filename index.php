<?php
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

