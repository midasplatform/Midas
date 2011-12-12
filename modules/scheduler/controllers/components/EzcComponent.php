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

// load ezc autoloader
set_include_path( BASE_PATH."/modules/scheduler/library/ezcomponents" . PATH_SEPARATOR .  get_include_path());
require_once "Base/src/base.php"; 
function __autoload( $className )
  {
  ezcBase::autoload( $className );
  }

class Scheduler_EzcComponent extends AppComponent
{ 
  public function initWorkflowDefinitionStorage()
    {
    $autoloader = Zend_Loader_Autoloader::getInstance();
    $autoloader->pushAutoloader(array('ezcBase', 'autoload'), 'ezc');
    // Set up database connection.
    $configDatabase = Zend_Registry::get('configDatabase');
    $db = ezcDbFactory::create( 'mysql://'.$configDatabase->database->params->username.':'.$configDatabase->database->params->password.'@'.$configDatabase->database->params->host.'/'.$configDatabase->database->params->dbname );

    // Set up workflow definition storage (database).
    $definition = new ezcWorkflowDatabaseDefinitionStorage( $db );
    $options = $definition->__get('options');
    $options->__set('prefix', 'scheduler_');
    return $definition;
    }
    
} // end class
?>