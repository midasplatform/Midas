<?php

/**
 * Admin Controller
 */
class AdminController extends AppController
{
  public $_models = array('Errorlog', 'Assetstore');
  public $_daos = array();
  public $_components = array('Upgrade', 'Utility', 'MIDAS2Migration');
  public $_forms = array('Admin', 'Assetstore');
  
  /** init the controller */
  function init()
    {
    $config = Zend_Registry::get('configGlobal'); //set admin part to english
    $config->application->lang = 'en';
    Zend_Registry::get('configGlobal', $config);
    }
    
  /** index*/
  function indexAction()
    {
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return;
      }
    if(!$this->userSession->Dao->getAdmin() == 1)
      {
      throw new Zend_Exception("You should be an administrator");
      }
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
        if(!file_exists($moduleConfigLocalFile))
          {
          copy($moduleConfigFile, $moduleConfigLocalFile);
          switch(Zend_Registry::get('configDatabase')->database->adapter)
            {
            case 'PDO_MYSQL':
              if(file_exists(BASE_PATH.'/modules/'.$moduleName.'/database/mysql/'.$allModules[$moduleName]->version.'.sql'))
                {
                $this->Component->Utility->run_mysql_from_file(BASE_PATH.'/modules/'.$moduleName.'/database/mysql/'.$allModules[$moduleName]->version.'.sql',
                                           Zend_Registry::get('configDatabase')->database->params->host,
                                           Zend_Registry::get('configDatabase')->database->params->username,
                                           Zend_Registry::get('configDatabase')->database->params->password,
                                           Zend_Registry::get('configDatabase')->database->params->dbname,
                                           Zend_Registry::get('configDatabase')->database->params->port);
                }
              break;
            case 'PDO_PGSQL':
              if(file_exists(BASE_PATH.'/modules/'.$key.'/database/pgsql/'.$allModules[$moduleName]->version.'.sql'))
                {
                $this->Component->Utility->run_pgsql_from_file(BASE_PATH.'/modules/'.$key.'/database/pgsql/'.$allModules[$moduleName]->version.'.sql',
                                           Zend_Registry::get('configDatabase')->database->params->host,
                                           Zend_Registry::get('configDatabase')->database->params->username,
                                           Zend_Registry::get('configDatabase')->database->params->password,
                                           Zend_Registry::get('configDatabase')->database->params->dbname,
                                           Zend_Registry::get('configDatabase')->database->params->port);
                }
              break;
            default:
              break;
            }
          }
        rename(BASE_PATH.'/core/configs/application.local.ini', BASE_PATH.'/core/configs/application.local.ini.old');
        $applicationConfig['module'][$moduleName] = $modulevalue;
        $this->Component->Utility->createInitFile(BASE_PATH.'/core/configs/application.local.ini', $applicationConfig);
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      }
      
    // get assetstore data
    $defaultAssetStoreId = Zend_Registry::get('configGlobal')->defaultassetstore->id;
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
      $assetstores[$key]->totalSpace = disk_total_space($assetstore->getPath());
      $assetstores[$key]->totalSpaceText = $this->Component->Utility->formatSize($assetstores[$key]->totalSpace);
      $assetstores[$key]->freeSpace = disk_free_space($assetstore->getPath());
      $assetstores[$key]->freeSpaceText = $this->Component->Utility->formatSize($assetstores[$key]->freeSpace);
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
    
    foreach($allModules as $key => $module)
      {
      if(file_exists(BASE_PATH."/modules/".$key."/controllers/ConfigController.php"))
        {
        $allModules[$key]->configPage = true;
        }
      else
        {
        $allModules[$key]->configPage = false;
        }
      }

    $this->view->modulesList = $allModules;
    $this->view->modulesEnable = $modulesEnable;
    $this->view->databaseType = Zend_Registry::get('configDatabase')->database->adapter;
    }//end indexAction
 
  /** show logs*/
  function showlogAction()
    {
    if(!$this->logged || !$this->userSession->Dao->getAdmin() == 1)
      {
      throw new Zend_Exception("You should be an administrator");
      }
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception("Why are you here ? Should be ajax.");
      }
    $this->_helper->layout->disableLayout();
    
    $start = $this->_getParam("startlog");
    $end = $this->_getParam("endlog");
    $module = $this->_getParam("modulelog");
    $priority = $this->_getParam("prioritylog");
    if(!isset($start))
      {
      $start = date('c', strtotime("-24 hour"));
      }
    else
      {
      $start = date('c', strtotime($start));
      }
    if(!isset($end))
      {
      $end = date('c');
      }
    else
      {
      $end = date('c', strtotime($end));
      }
    if(!isset($module))
      {
      $module = 'all';
      }
    if(!isset($priority))
      {
      $priority = 'all';
      }
      
    $logs = $this->Errorlog->getLog($start, $end, $module, $priority);
    foreach($logs as $key => $log)
      {
      $logs[$key] = $log->toArray();
      if(substr($log->getMessage(), 0, 5) == 'Fatal')
        {
        $shortMessage = substr($log->getMessage(), strpos($log->getMessage(), "[message]") + 10, 40);
        }
      elseif(substr($log->getMessage(), 0, 6) == 'Server')
        {
        $shortMessage = substr($log->getMessage(), strpos($log->getMessage(), "Message:") + 9, 40);
        }
      else
        {
        $shortMessage = substr($log->getMessage(), 0, 40);
        }
      $logs[$key]['shortMessage'] = $shortMessage.' ...';
      }
    $this->view->jsonLogs = JsonComponent::encode($logs);
    $this->view->jsonLogs = htmlentities($this->view->jsonLogs);
    
    if($this->_request->isPost())
      {
      $this->_helper->viewRenderer->setNoRender();
      echo $this->view->jsonLogs;
      return;
      }
      
    $modulesConfig = Zend_Registry::get('configsModules');
      
    $modules = array('all', 'core');
    foreach($modulesConfig as $key => $module)
      {
      $modules[] = $key;
      }    
    $this->view->modulesLog = $modules;
    }//showlogAction
    
  /** function dashboard*/
  function dashboardAction()
    {
    if(!$this->logged || !$this->userSession->Dao->getAdmin() == 1)
      {
      throw new Zend_Exception("You should be an administrator");
      }
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception("Why are you here ? Should be ajax.");
      }
      
    $this->_helper->layout->disableLayout();
    
    $this->view->dashboard =  Zend_Registry::get('notifier')->notify(MIDAS_NOTIFY_GET_DASBOARD);
    
    }//end dashboardAction
    
  /** upgrade database*/
  function upgradeAction()
    {
    if(!$this->logged || !$this->userSession->Dao->getAdmin() == 1)
      {
      throw new Zend_Exception("You should be an administrator");
      }
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception("Why are you here ? Should be ajax.");
      }
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
      throw new Zend_Exception("You should be logged in");
      }
    if(!$this->userSession->Dao->isAdmin())
      {
      throw new Zend_Exception("Administrative privileges required");
      }     
    
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
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $this->Component->MIDAS2Migration->migrate();
    }
    
} // end class

  