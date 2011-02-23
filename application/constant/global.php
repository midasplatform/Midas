<?php
/* 
 * Define Constants
 */

/**
 * Config files
 */

if (file_exists(BASE_PATH . '/application/configs/core.local.ini'))
  {
  define('CORE_CONFIG', BASE_PATH . '/application/configs/core.local.ini');
  }
else
  {
  define('CORE_CONFIG', BASE_PATH . '/application/configs/core.ini');
  }

if (file_exists(BASE_PATH . '/application/configs/application.local.ini'))
  {
  define('APPLICATION_CONFIG', BASE_PATH . '/application/configs/application.local.ini');
  }
else
  {
  define('APPLICATION_CONFIG', BASE_PATH . '/application/configs/application.ini');
  }

if (file_exists(BASE_PATH . '/application/configs/database.local.ini'))
  {
  define('DATABASE_CONFIG', BASE_PATH . '/application/configs/database.local.ini');
  }
else
  {
  define('DATABASE_CONFIG', BASE_PATH . '/application/configs/database.ini');
  }
?>
