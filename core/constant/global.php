<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

if (file_exists(BASE_PATH . '/core/configs/core.local.ini'))
  {
  define('CORE_CONFIG', BASE_PATH . '/core/configs/core.local.ini');
  }
else
  {
  define('CORE_CONFIG', BASE_PATH . '/core/configs/core.ini');
  }

if (file_exists(BASE_PATH . '/core/configs/application.local.ini'))
  {
  define('APPLICATION_CONFIG', BASE_PATH . '/core/configs/application.local.ini');
  }
else
  {
  define('APPLICATION_CONFIG', BASE_PATH . '/core/configs/application.ini');
  }

if (file_exists(BASE_PATH . '/core/configs/database.local.ini'))
  {
  define('DATABASE_CONFIG', BASE_PATH . '/core/configs/database.local.ini');
  }
else
  {
  define('DATABASE_CONFIG', BASE_PATH . '/core/configs/database.ini');
  }
?>