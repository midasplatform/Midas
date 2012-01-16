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

/**
 * Admin Controller
 */
class AdminController extends AppController
{
  public $_models = array('Errorlog', 'Assetstore');
  public $_daos = array();
  public $_components = array('Upgrade', 'Utility', 'MIDAS2Migration', 'Demo');
  public $_forms = array('Admin', 'Assetstore', 'Migrate');

  /** init the controller */
  function init()
    {
    $config = Zend_Registry::get('configGlobal'); //set admin part to english
    $config->application->lang = 'en';
    Zend_Registry::get('configGlobal', $config);
    if($this->isDemoMode())
      {
      $this->disableView();
      $this->render('unavailable');
      }
    }

  /** reset Demo*/
  function resetdemoAction()
    {
    if(!$this->isDemoMode())
      {
      throw new Zend_Exception("Please enable demo mode");
      }
    $this->Component->Demo->reset();
    $this->disableLayout();
    $this->disableView();
    }

  /** run a task **/
  function taskAction()
    {
    set_time_limit(0);
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return false;
      }
    $this->requireAdminPrivileges();

    $task = $this->_getParam("task");
    $params = $this->_getParam("params");
    if(isset($params))
      {
      $params = JsonComponent::decode($params);
      }

    $modules = Zend_Registry::get('notifier')->modules;
    $tasks = Zend_Registry::get('notifier')->tasks;
    call_user_func(array($modules[$tasks[$task]['module']], $tasks[$task]['method']), $params);
    $this->disableLayout();
    $this->disableView();
    }

  /** index*/
  function indexAction()
    {
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return false;
      }
    $this->requireAdminPrivileges();
    $this->view->header = "Administration";
    $configForm = $this->Form->Admin->createConfigForm();

    $applicationConfig = parse_ini_file(BASE_PATH.'/core/configs/application.local.ini', true);
    $formArray = $this->getFormAsArray($configForm);

    $formArray['name']->setValue($applicationConfig['global']['application.name']);
    $formArray['keywords']->setValue($applicationConfig['global']['application.keywords']);
    $formArray['description']->setValue($applicationConfig['global']['application.description']);
    $formArray['environment']->setValue($applicationConfig['global']['environment']);
    $formArray['lang']->setValue($applicationConfig['global']['application.lang']);
    $formArray['smartoptimizer']->setValue($applicationConfig['global']['smartoptimizer']);
    $formArray['timezone']->setValue($applicationConfig['global']['default.timezone']);
    if(isset($applicationConfig['global']['dynamichelp']))
      {
      $formArray['dynamichelp']->setValue($applicationConfig['global']['dynamichelp']);
      }
    $this->view->selectedLicense = $applicationConfig['global']['defaultlicense'];
    $this->view->configForm = $formArray;

    $allModules = $this->Component->Utility->getAllModules();

    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->_getParam('submitConfig');
      $submitModule = $this->_getParam('submitModule');
      if(isset($submitConfig))
        {
        $applicationConfig = parse_ini_file(BASE_PATH.'/core/configs/application.local.ini', true);
        if(file_exists(BASE_PATH.'/core/configs/application.local.ini.old'))
          {
          unlink(BASE_PATH.'/core/configs/application.local.ini.old');
          }
        rename(BASE_PATH.'/core/configs/application.local.ini', BASE_PATH.'/core/configs/application.local.ini.old');
        $applicationConfig['global']['application.name'] = $this->_getParam('name');
        $applicationConfig['global']['application.description'] = $this->_getParam('description');
        $applicationConfig['global']['application.keywords'] = $this->_getParam('keywords');
        $applicationConfig['global']['application.lang'] = $this->_getParam('lang');
        $applicationConfig['global']['environment'] = $this->_getParam('environment');
        $applicationConfig['global']['smartoptimizer'] = $this->_getParam('smartoptimizer');
        $applicationConfig['global']['default.timezone'] = $this->_getParam('timezone');
        $applicationConfig['global']['defaultlicense'] = $this->_getParam('licenseSelect');
        $applicationConfig['global']['dynamichelp'] = $this->_getParam('dynamichelp');
        $this->Component->Utility->createInitFile(BASE_PATH.'/core/configs/application.local.ini', $applicationConfig);
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      if(isset($submitModule))
        {
        $moduleName = $this->_getParam('modulename');
        $modulevalue = $this->_getParam('modulevalue');
        $applicationConfig = parse_ini_file(BASE_PATH.'/core/configs/application.local.ini', true);
        if(file_exists(BASE_PATH.'/core/configs/application.local.ini.old'))
          {
          unlink(BASE_PATH.'/core/configs/application.local.ini.old');
          }

        $moduleConfigLocalFile = BASE_PATH."/core/configs/".$moduleName.".local.ini";
        $moduleConfigFile = BASE_PATH."/modules/".$moduleName."/configs/module.ini";
        $moduleConfigPrivateFile = BASE_PATH."/privateModules/".$moduleName."/configs/module.ini";
        if(!file_exists($moduleConfigLocalFile) && file_exists($moduleConfigFile))
          {
          copy($moduleConfigFile, $moduleConfigLocalFile);
          $this->Component->Utility->installModule($moduleName);
          }
        elseif(!file_exists($moduleConfigLocalFile) && file_exists($moduleConfigPrivateFile))
          {
          copy($moduleConfigPrivateFile, $moduleConfigLocalFile);
          $this->Component->Utility->installModule($moduleName);
          }
        elseif(!file_exists($moduleConfigLocalFile))
          {
          throw new Zend_Exception("Unable to find config file");
          }

        rename(BASE_PATH.'/core/configs/application.local.ini', BASE_PATH.'/core/configs/application.local.ini.old');
        $applicationConfig['module'][$moduleName] = $modulevalue;
        $this->Component->Utility->createInitFile(BASE_PATH.'/core/configs/application.local.ini', $applicationConfig);
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      }

    // Disable default assetstore feature is version less than 3.1.4
    if($this->Component->Upgrade->transformVersionToNumeric(Zend_Registry::get('configDatabase')->version) < $this->Component->Upgrade->transformVersionToNumeric("3.1.4") )
      {
      $defaultAssetStoreId = 0;
      }
    else
      {
      $defaultAssetStoreId = $this->Assetstore->getDefault()->getKey();
      }

    // get assetstore data
    $assetstores = $this->Assetstore->getAll();
    $defaultSet = false;
    foreach($assetstores as $key => $assetstore)
      {
      if($assetstore->getKey() == $defaultAssetStoreId)
        {
        $assetstores[$key]->default = true;
        $defaultSet = true;
        }
      else
        {
        $assetstores[$key]->default = false;
        }

      // Check if we can access the path
      if(file_exists($assetstore->getPath()))
        {
        $assetstores[$key]->totalSpace = disk_total_space($assetstore->getPath());
        $assetstores[$key]->totalSpaceText = $this->Component->Utility->formatSize($assetstores[$key]->totalSpace);
        $assetstores[$key]->freeSpace = disk_free_space($assetstore->getPath());
        $assetstores[$key]->freeSpaceText = $this->Component->Utility->formatSize($assetstores[$key]->freeSpace);
        }
      else
        {
        $assetstores[$key]->totalSpaceText = false;
        }
      }

    if(!$defaultSet)
      {
      foreach($assetstores as $key => $assetstore)
        {
        $assetstores[$key]->default = true;
        $applicationConfig = parse_ini_file(BASE_PATH.'/core/configs/application.local.ini', true);
        $applicationConfig['global']['defaultassetstore.id'] = $assetstores[$key]->getKey();
        $this->Component->Utility->createInitFile(BASE_PATH.'/core/configs/application.local.ini', $applicationConfig);
        break;
        }
      }
    $this->view->assetstores = $assetstores;
    $this->view->assetstoreForm = $this->Form->Assetstore->createAssetstoreForm();

    // get modules
    $modulesEnable = Zend_Registry::get('modulesEnable');
    $adapter = Zend_Registry::get('configDatabase')->database->adapter;
    foreach($allModules as $key => $module)
      {
      if(file_exists(BASE_PATH."/modules/".$key."/controllers/ConfigController.php"))
        {
        $allModules[$key]->configPage = true;
        }
      elseif(file_exists(BASE_PATH."/privateModules/".$key."/controllers/ConfigController.php"))
        {
        $allModules[$key]->configPage = true;
        }
      else
        {
        $allModules[$key]->configPage = false;
        }

      if(isset($module->db->$adapter))
        {
        $allModules[$key]->dbOk = true;
        }
      else
        {
        $allModules[$key]->dbOk = false;
        }

      $allModules[$key]->dependenciesArray = array();
      $allModules[$key]->dependenciesExist = true;
      // check if dependencies exit
      if(isset($allModules[$key]->dependencies) && !empty($allModules[$key]->dependencies))
        {
        $allModules[$key]->dependenciesArray = explode(',', trim($allModules[$key]->dependencies));
        foreach($allModules[$key]->dependenciesArray as $dependency)
          {
          if(!isset($allModules[$dependency]))
            {
            $allModules[$key]->dependenciesExist = false;
            }
          }
        }
      }

    $modulesList = array();
    $countModules = array();
    foreach($allModules as $k => $module)
      {
      if(!isset($module->category) || empty($module->category))
        {
        $category = "Misc";
        }
      else
        {
        $category = ucfirst(strtolower($module->category));
        }
      if(!isset($modulesList[$category]))
        {
        $modulesList[$category] = array();
        $countModules[$category] = array('visible' => 0, 'hidden' => 0);
        }
      $modulesList[$category][$k] = $module;
      if($module->dbOk && $module->dependenciesExist)
        {
        $countModules[$category]['visible']++;
        }
      else
        {
        $countModules[$category]['hidden']++;
        }
      }

    foreach($modulesList as $k => $l)
      {
      ksort($modulesList[$k]);
      }

    ksort($modulesList);
    $this->view->countModules = $countModules;
    $this->view->modulesList = $modulesList;
    $this->view->modulesEnable = $modulesEnable;
    $this->view->databaseType = Zend_Registry::get('configDatabase')->database->adapter;
    }//end indexAction

  /** Used to display and filter the list of log messages */
  function showlogAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();

    $start = $this->_getParam('startlog');
    $end = $this->_getParam('endlog');
    $module = $this->_getParam('modulelog');
    $priority = $this->_getParam('prioritylog');
    if(!isset($start) || empty($start))
      {
      $start = date('c', strtotime('-24 hour'));
      }
    else
      {
      $start = date('c', strtotime($start));
      }
    if(!isset($end) || empty($end))
      {
      $end = date('c');
      }
    else
      {
      $end = date('c', strtotime($end));
      }
    if(!isset($module) || empty($module))
      {
      $module = 'all';
      }
    if(!isset($priority) || empty($priority))
      {
      $priority = 'all';
      }

    $this->view->currentFilter = array('start' => $start,
                                       'end' => $end,
                                       'module' => $module,
                                       'priority' => $priority);

    $logs = $this->Errorlog->getLog($start, $end, $module, $priority);
    foreach($logs as $key => $log)
      {
      $logs[$key] = $log->toArray();
      if(substr($log->getMessage(), 0, 5) == 'Fatal')
        {
        $shortMessage = substr($log->getMessage(), strpos($log->getMessage(), '[message]') + 13, 60);
        }
      elseif(substr($log->getMessage(), 0, 6) == 'Server')
        {
        $shortMessage = substr($log->getMessage(), strpos($log->getMessage(), 'Message:') + 9, 60);
        }
      else
        {
        $shortMessage = substr($log->getMessage(), 0, 60);
        }
      $logs[$key]['shortMessage'] = $shortMessage.' ...';
      }

    if($this->_request->isPost())
      {
      $this->_helper->viewRenderer->setNoRender();
      echo JsonComponent::encode(array('currentFilter' => $this->view->currentFilter,
                                       'logs' => $logs));
      return;
      }

    $modulesConfig = Zend_Registry::get('configsModules');
    $modules = array('all', 'core');
    foreach($modulesConfig as $key => $module)
      {
      $modules[] = $key;
      }
    $this->view->modulesLog = $modules;
    $this->view->jsonLogs = htmlentities(JsonComponent::encode($logs));
    }//showlogAction

  /** Used to delete a list of log entries */
  function deletelogAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $ids = $this->_getParam('idList');
    $count = 0;
    foreach(explode(',', $ids) as $id)
      {
      if(!empty($id) && is_numeric($id))
        {
        $count++;
        $dao = $this->Errorlog->load($id);
        if($dao)
          {
          $this->Errorlog->delete($dao);
          }
        }
      }

    echo JsonComponent::encode(array('message' => 'Successfully deleted '.$count.' entries.'));
    return;
    }

  /** function dashboard*/
  function dashboardAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();

    $this->view->dashboard = Zend_Registry::get('notifier')->callback("CALLBACK_CORE_GET_DASHBOARD");

    ksort($this->view->dashboard);

    }//end dashboardAction

  /** upgrade database*/
  function upgradeAction()
    {
    $this->requireAdminPrivileges();
    $this->requireAjaxRequest();
    $this->_helper->layout->disableLayout();

    $db = Zend_Registry::get('dbAdapter');
    $dbtype = Zend_Registry::get('configDatabase')->database->adapter;
    $modulesConfig = Zend_Registry::get('configsModules');

    if($this->_request->isPost())
      {
      $this->_helper->viewRenderer->setNoRender();
      $upgraded = false;
      $modulesConfig = Zend_Registry::get('configsModules');
      $modules = array();
      foreach($modulesConfig as $key => $module)
        {
        $this->Component->Upgrade->initUpgrade($key, $db, $dbtype);
        $upgraded = $upgraded || $this->Component->Upgrade->upgrade($module->version);
        }
      $this->Component->Upgrade->initUpgrade('core', $db, $dbtype);
      $upgraded = $upgraded || $this->Component->Upgrade->upgrade(Zend_Registry::get('configDatabase')->version);
      $this->view->upgraded = $upgraded;

      $dbtype = Zend_Registry::get('configDatabase')->database->adapter;
      $modulesConfig = Zend_Registry::get('configsModules');
      if($upgraded)
        {
        echo JsonComponent::encode(array(true, 'Upgraded'));
        }
      else
        {
        echo JsonComponent::encode(array(false, 'Nothing to upgrade'));
        }
      return;
      }

    $modules = array();
    foreach($modulesConfig as $key => $module)
      {
      $this->Component->Upgrade->initUpgrade($key, $db, $dbtype);
      $modules[$key]['target'] = $this->Component->Upgrade->getNewestVersion();
      $modules[$key]['targetText'] = $this->Component->Upgrade->getNewestVersion(true);
      $modules[$key]['currentText'] = $module->version;
      $modules[$key]['current'] = $this->Component->Upgrade->transformVersionToNumeric($module->version);
      }

    $this->view->modules = $modules;

    $this->Component->Upgrade->initUpgrade('core', $db, $dbtype);
    $core['target'] = $this->Component->Upgrade->getNewestVersion();
    $core['targetText'] = $this->Component->Upgrade->getNewestVersion(true);
    $core['currentText'] = Zend_Registry::get('configDatabase')->version;
    $core['current'] = $this->Component->Upgrade->transformVersionToNumeric(Zend_Registry::get('configDatabase')->version);
    $this->view->core = $core;
    }//end upgradeAction

  /**
   * \fn serversidefilechooser()
   * \brief called by the server-side file chooser
   */
  function serversidefilechooserAction()
    {
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return false;
      }
    $this->requireAdminPrivileges();

    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    // Display the tree
    $_POST['dir'] = urldecode($_POST['dir']);
    $files = array();
    if(strpos(strtolower(PHP_OS), 'win') !==  false)
      {
      $files = array();
      for($c = 'A'; $c <= 'Z'; $c++)
        {
        if(is_dir($c . ':'))
          {
          $files[] = $c . ':';
          }
        }
      }
    else
      {
      $files[] = '/';
      }

    if(file_exists($_POST['dir']) || file_exists($files[0]))
      {
      if(file_exists($_POST['dir']))
        {
        $files = scandir($_POST['dir']);
        }
      natcasesort($files);
      echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
      foreach($files as $file)
        {
        if(file_exists($_POST['dir'] . $file) && $file != '.' && $file != '..' && is_readable($_POST['dir'] . $file))
          {
          if(is_dir($_POST['dir'] . $file))
            {
            echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "/\">" . htmlentities($file) . "</a></li>";
            }
          else// not a directory: a file!
            {
            $ext = preg_replace('/^.*\./', '', $file);
            echo "<li class=\"file ext_".$ext."\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "\">" . htmlentities($file) . "</a></li>";
            }
          }
        }
      echo "</ul>";
      }
    else
      {
      echo "File ".$_POST['dir']." doesn't exist";
      }
    // No views
    } // end function  serversidefilechooserAction


  /**
   * \fn
   * \brief
   */
  function migratemidas2Action()
    {
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return false;
      }
    $this->requireAdminPrivileges();

    $this->assetstores = $this->Assetstore->getAll();
    $this->view->migrateForm = $this->Form->Migrate->createMigrateForm($this->assetstores);
    $this->view->assetstoreForm = $this->Form->Assetstore->createAssetstoreForm('../assetstore/add');

    if($this->getRequest()->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();

      if(!$this->view->migrateForm->isValid($_POST))
        {
        echo json_encode(array('error' => $this->t('The form is invalid. Missing values.')));
        return false;
        }

      $midas2_hostname = $_POST['midas2_hostname'];
      $midas2_port = $_POST['midas2_port'];
      $midas2_user = $_POST['midas2_user'];
      $midas2_password = $_POST['midas2_password'];
      $midas2_database = $_POST['midas2_database'];
      $midas2_assetstore = $_POST['midas2_assetstore'];
      $midas3_assetstore = $_POST['assetstore'];

      // Check that the assetstore is accessible
      if(!file_exists($midas2_assetstore))
        {
        echo json_encode(array('error' => $this->t('MIDAS2 assetstore is not accessible.')));
        return false;
        }

      // Remove the last slashe if any
      if($midas2_assetstore[strlen($midas2_assetstore) - 1] == '\\'
         || $midas2_assetstore[strlen($midas2_assetstore) - 1] == '/')
        {
        $midas2_assetstore = substr($midas2_assetstore, 0, strlen($midas2_assetstore) - 1);
        }

      $this->Component->MIDAS2Migration->midas2User = $midas2_user;
      $this->Component->MIDAS2Migration->midas2Password = $midas2_password;
      $this->Component->MIDAS2Migration->midas2Host = $midas2_hostname;
      $this->Component->MIDAS2Migration->midas2Database = $midas2_database;
      $this->Component->MIDAS2Migration->midas2Port = $midas2_port;
      $this->Component->MIDAS2Migration->midas2Assetstore = $midas2_assetstore;
      $this->Component->MIDAS2Migration->assetstoreId = $midas3_assetstore;

      try
        {
        $this->Component->MIDAS2Migration->migrate($this->userSession->Dao->getUserId());
        }
      catch(Zend_Exception $e)
        {
        echo json_encode(array('error' => $this->t($e->getMessage())));
        return false;
        }

      echo json_encode(array('message' => $this->t('Migration sucessful.')));
      }

    // Display the form
    }

} // end class
