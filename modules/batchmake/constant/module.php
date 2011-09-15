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
define("MIDAS_BATCHMAKE_CHECK_IF_READABLE", 0x2);
define("MIDAS_BATCHMAKE_CHECK_IF_WRITABLE", 0x4);
define("MIDAS_BATCHMAKE_CHECK_IF_RW",  0x6); // 0x2 + 0x4;
define("MIDAS_BATCHMAKE_CHECK_IF_EXECUTABLE", 0x8);
define("MIDAS_BATCHMAKE_CHECK_IF_CHMODABLE", 0x10);
define("MIDAS_BATCHMAKE_CHECK_IF_CHMODABLE_RW", 0x16); // 0x10 + 0x6



// Condor executables
define("MIDAS_BATCHMAKE_CONDOR_STATUS", "condor_status");
define("MIDAS_BATCHMAKE_CONDOR_QUEUE", "condor_q");
define("MIDAS_BATCHMAKE_CONDOR_SUBMIT", "condor_submit");
define("MIDAS_BATCHMAKE_CONDOR_SUBMIT_DAG", "condor_submit_dag");

// Batchmake executable
define("MIDAS_BATCHMAKE_EXE", "BatchMake");
// Batchmake temporary execution dir
define("MIDAS_BATCHMAKE_SSP_DIR", "SSP");


// Extension automatically appended to dagman 
// description file when 'condor_dag_submit' generates it
define("MIDAS_BATCHMAKE_CONDOR_DAGMAN_EXT", ".condor.sub");
  
// default extension for a dagjob
define("MIDAS_BATCHMAKE_CONDOR_SCRIPT_EXT", ".dagjob");
// extension for a batchmake script
define("MIDAS_BATCHMAKE_BATCHMAKE_EXTENSION", ".bms");

// properties set in the configuration
define("MIDAS_BATCHMAKE_TMP_DIR_PROPERTY", "tmp_dir");
define("MIDAS_BATCHMAKE_BIN_DIR_PROPERTY", "bin_dir");
define("MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY", "script_dir");
define("MIDAS_BATCHMAKE_APP_DIR_PROPERTY", "app_dir");
define("MIDAS_BATCHMAKE_DATA_DIR_PROPERTY", "data_dir");
define("MIDAS_BATCHMAKE_CONDOR_BIN_DIR_PROPERTY", "condor_bin_dir");

// key for global config
define("MIDAS_BATCHMAKE_GLOBAL_CONFIG_NAME", "global");
 
// status types
define("MIDAS_BATCHMAKE_STATUS_TYPE_INFO", "info");
define("MIDAS_BATCHMAKE_STATUS_TYPE_WARNING", "warning");
define("MIDAS_BATCHMAKE_STATUS_TYPE_ERROR", "error");


// config states
define("MIDAS_BATCHMAKE_CONFIG_VALUE_MISSING", "config value missing");
define("MIDAS_BATCHMAKE_CONFIG_CORRECT", "Configuration is correct");
define("MIDAS_BATCHMAKE_CONFIG_ERROR", "Configuration is in error");

// config paths
define("MIDAS_BATCHMAKE_MODULE", "batchmake");
define("MIDAS_BATCHMAKE_CONFIGS_PATH", BASE_PATH . "/modules/" . MIDAS_BATCHMAKE_MODULE . "/configs/");
define("MIDAS_BATCHMAKE_MODULE_CONFIG", MIDAS_BATCHMAKE_CONFIGS_PATH . "module.ini");
define("MIDAS_BATCHMAKE_MODULE_LOCAL_CONFIG", MIDAS_BATCHMAKE_CONFIGS_PATH . "module.local.ini");
define("MIDAS_BATCHMAKE_MODULE_LOCAL_OLD_CONFIG", MIDAS_BATCHMAKE_CONFIGS_PATH . "module.local.ini.old");


// submit button name for config
define("MIDAS_BATCHMAKE_SUBMIT_CONFIG","submitConfig");




// strings
define("MIDAS_BATCHMAKE_CHANGES_SAVED_STRING", 'Changes saved');
define("MIDAS_BATCHMAKE_APPLICATION_STRING", 'Application:');
define("MIDAS_BATCHMAKE_PHP_PROCESS_STRING", 'PHP Process');
define("MIDAS_BATCHMAKE_PHP_PROCESS_USER_STRING", 'user');
define("MIDAS_BATCHMAKE_PHP_PROCESS_NAME_STRING", 'name');
define("MIDAS_BATCHMAKE_PHP_PROCESS_GROUP_STRING", 'group');
define("MIDAS_BATCHMAKE_PHP_PROCESS_HOME_STRING", 'home');
define("MIDAS_BATCHMAKE_PHP_PROCESS_SHELL_STRING", 'shell');
define("MIDAS_BATCHMAKE_UNKNOWN_STRING", 'unknown');
define("MIDAS_BATCHMAKE_AJAX_DIRECT_LOAD_ERROR_STRING", 'Why are you here ? Should be ajax.');
define("MIDAS_BATCHMAKE_SAVE_CONFIGURATION_STRING", 'Save configuration');
define("MIDAS_BATCHMAKE_EXIST_STRING", 'Exist');
define("MIDAS_BATCHMAKE_NOT_FOUND_ON_CURRENT_SYSTEM_STRING", 'Not found on the current system');
define("MIDAS_BATCHMAKE_FILE_OR_DIRECTORY_DOESNT_EXIST_STRING", "File or directory doesn't exist:");

define("MIDAS_BATCHMAKE_NO_SCRIPT_SPECIFIED", "No script specified");
define("MIDAS_BATCHMAKE_NO_SCRIPT_FOUND", "No script found at: ");
define("MIDAS_BATCHMAKE_CREATE_TMP_DIR_FAILED", "Failed to create temporary directory: ");
define("MIDAS_BATCHMAKE_SYMLINK_FAILED", "Failed to create symbolic link: ");


// property keys
define("MIDAS_BATCHMAKE_DIR_KEY",'dir');
define("MIDAS_BATCHMAKE_PROPERTY_KEY",'property');
define("MIDAS_BATCHMAKE_STATUS_KEY",'status');
define("MIDAS_BATCHMAKE_TYPE_KEY",'type');


// properties for the batchmake execution view
define("MIDAS_BATCHMAKE_AVAILABLE_SCRIPTS",'Batchmake Scripts');
define("MIDAS_BATCHMAKE_EDIT_CONFIG",'edit Batchmake configuration');

    
?>
