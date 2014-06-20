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

define('CORE_CONFIGS_PATH', BASE_PATH . '/core/configs');
define('LOCAL_CONFIGS_PATH', CORE_CONFIGS_PATH);
define('LOGS_PATH', BASE_PATH . '/log');

if(file_exists(LOCAL_CONFIGS_PATH . '/core.local.ini'))
  {
  define('CORE_CONFIG', LOCAL_CONFIGS_PATH . '/core.local.ini');
  }
else
  {
  define('CORE_CONFIG', CORE_CONFIGS_PATH . '/core.ini');
  }

if(file_exists(LOCAL_CONFIGS_PATH . '/application.local.ini'))
  {
  define('APPLICATION_CONFIG', LOCAL_CONFIGS_PATH . '/application.local.ini');
  }
else
  {
  define('APPLICATION_CONFIG', CORE_CONFIGS_PATH . '/application.ini');
  }

if(file_exists(LOCAL_CONFIGS_PATH . '/database.local.ini'))
  {
  define('DATABASE_CONFIG', LOCAL_CONFIGS_PATH . '/database.local.ini');
  }
else
  {
  define('DATABASE_CONFIG', CORE_CONFIGS_PATH . '/database.ini');
  }
