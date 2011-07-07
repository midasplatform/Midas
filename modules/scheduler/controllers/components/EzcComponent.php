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