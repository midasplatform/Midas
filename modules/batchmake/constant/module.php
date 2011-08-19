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
define("CHECK_IF_READABLE", 0x2);
define("CHECK_IF_WRITABLE", 0x4);
define("CHECK_IF_RW",  0x6); // 0x2 + 0x4;
define("CHECK_IF_EXECUTABLE", 0x8);
define("CHECK_IF_CHMODABLE", 0x10);
define("CHECK_IF_CHMODABLE_RW", 0x16); // 0x10 + 0x6

define("DEFAULT_MKDIR_MODE", 0775);

// Condor executables
define("CONDOR_STATUS", "condor_status");
define("CONDOR_QUEUE", "condor_q");
define("CONDOR_SUBMIT", "condor_submit");
define("CONDOR_SUBMIT_DAG", "condor_submit_dag");

// Batchmake executable
define("BATCHMAKE_EXE", "BatchMake");
  
// Extension automatically appended to dagman 
// description file when 'condor_dag_submit' generates it
define("CONDOR_DAGMAN_EXT", ".condor.sub");
  
// default extension for a dagjob
define("CONDOR_SCRIPT_EXT", ".dagjob");

// properties set in the configuration
define("TMP_DIR_PROPERTY", "tmp_dir");
define("BIN_DIR_PROPERTY", "bin_dir");
define("SCRIPT_DIR_PROPERTY", "script_dir");
define("APP_DIR_PROPERTY", "app_dir");
define("DATA_DIR_PROPERTY", "data_dir");
define("CONDOR_BIN_DIR_PROPERTY", "condor_bin_dir");

// key for global config
define("GLOBAL_CONFIG_NAME", "global");
 
// status types
define("STATUS_TYPE_INFO", "info");
define("STATUS_TYPE_WARNING", "warning");
define("STATUS_TYPE_ERROR", "error");
define("CONFIG_VALUE_MISSING", "config value missing");


// config paths
define("BATCHMAKE_MODULE", "batchmake");
define("BATCHMAKE_CONFIGS_PATH", BASE_PATH . "/modules/" . BATCHMAKE_MODULE . "/configs/");
define("BATCHMAKE_MODULE_CONFIG", BATCHMAKE_CONFIGS_PATH . "module.ini");
define("BATCHMAKE_MODULE_LOCAL_CONFIG", BATCHMAKE_CONFIGS_PATH . "module.local.ini");
define("BATCHMAKE_MODULE_LOCAL_OLD_CONFIG", BATCHMAKE_CONFIGS_PATH . "module.local.ini.old");



// strings
define("CHANGES_SAVED_STRING", 'Changes saved');
define("APPLICATION_STRING", 'Application:');
define("PHP_PROCESS_STRING", 'PHP Process');
define("PHP_PROCESS_USER_STRING", 'user');
define("PHP_PROCESS_NAME_STRING", 'name');
define("PHP_PROCESS_GROUP_STRING", 'group');
define("PHP_PROCESS_HOME_STRING", 'home');
define("PHP_PROCESS_SHELL_STRING", 'shell');
define("UNKNOWN_STRING", 'unknown');
define("AJAX_DIRECT_LOAD_ERROR_STRING", 'Why are you here ? Should be ajax.');
define("SAVE_CONFIGURATION_STRING", 'Save configuration');
define("EXIST_STRING", 'Exist');
define("NOT_FOUND_ON_CURRENT_SYSTEM_STRING", 'Not found on the current system');
define("FILE_OR_DIRECTORY_DOESNT_EXIST_STRING", "File or directory doesn't exist:");




// property keys
define("DIR_KEY",'dir');
define("PROPERTY_KEY",'property');
define("STATUS_KEY",'status');
define("TYPE_KEY",'type');



    

?>
