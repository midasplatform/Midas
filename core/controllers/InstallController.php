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
 *  InstallController
 */
class InstallController extends AppController
{
  public $_models = array('User', 'Assetstore');
  public $_daos = array('Assetstore');
  public $_components = array('Utility');
  public $_forms = array('Install');

  /**
   * @method init()
   */
  function init()
    {
    if(file_exists(BASE_PATH."/core/configs/database.local.ini") &&
       file_exists(BASE_PATH."/core/configs/application.local.ini"))
      {
      throw new Zend_Exception("Midas is already installed.");
      }
    } //end init

  /**
   * @method indexAction()
   */
  function indexAction()
    {
    if(file_exists(BASE_PATH."/core/configs/database.local.ini"))
      {
      $this->_redirect('/install/step3');
      }
    $this->view->header = "Step1: Server Configuration";
        // Check PHP extension / function
    $phpextensions = array (
      "simplexml"  => array(false, ""),
    );
    $this->view->phpextension_missing = $this->Component->Utility->checkPhpExtensions($phpextensions);
    $this->view->writable = is_writable(BASE_PATH.'/core/configs');
    $this->view->basePath = BASE_PATH;
    if(!empty($_POST) && $this->view->writable)
      {
      $this->_redirect("/install/step2");
      }
    } // end method indexAction


  /**
   * @method step2Action()
   */
  function step2Action()
    {
    if(file_exists(BASE_PATH."/core/configs/database.local.ini"))
      {
      $this->_redirect('/install/step3');
      }
    $this->view->header = "Step2: Database Configuration";
        // Check PHP extension / function
    $phpextensions = array (
      "mysql"  => array(false, ''),
      "pgsql"    => array(false, ''),
      "oci" => array(false, ''),
      "sqlite" => array(false, ''),
      "ibm" => array(false, ''),
    );

    $this->view->databaseType = array();

    foreach($phpextensions as $key => $t)
      {
      if(!file_exists(BASE_PATH."/core/database/".$key))
        {
        unset($phpextensions[$key]);
        }
      else
        {
        $form = $this->Form->Install->createDBForm($key);
        $port = $form->getElement('port');
        switch($key)
          {
          case 'mysql':
            $port->setValue('3306');
            break;
          case 'pgsql':
            $port->setValue('5432');
            break;
          default:
            break;
          }
        $this->view->databaseType[$key] = $this->getFormAsArray($form);
        }
      }
    $this->view->phpextension_missing = $this->Component->Utility->checkPhpExtensions($phpextensions);
    $this->view->writable = is_writable(BASE_PATH);
    $this->view->convertfound = $this->Component->Utility->isImageMagickWorking();
    $this->view->basePath = BASE_PATH;

    if($this->_request->isPost())
      {
      $type = $this->_getParam('type');
      $form = $this->Form->Install->createDBForm($type);
      if($form->isValid($this->getRequest()->getPost()))
        {
        $databaseConfig = parse_ini_file(BASE_PATH.'/core/configs/database.ini', true);
        $MyDirectory = opendir(BASE_PATH."/core/database/".$type);

        require_once BASE_PATH.'/core/controllers/components/UpgradeComponent.php';
        $upgradeComponent = new UpgradeComponent();
        $upgradeComponent->dir = BASE_PATH."/core/database/".$type;
        $upgradeComponent->init = true;
        $sqlFile = $upgradeComponent->getNewestVersion(true);
        $sqlFile = BASE_PATH."/core/database/".$type."/".$sqlFile.'.sql';
        if(!isset($sqlFile) || !file_exists($sqlFile))
          {
          throw new Zend_Exception("Unable to find sql file");
          }

        switch($type)
          {
          case 'mysql':
            $this->Component->Utility->run_mysql_from_file($sqlFile,
                                       $form->getValue('host'), $form->getValue('username'), $form->getValue('password'), $form->getValue('dbname'), $form->getValue('port'));
            $params = array(
              'host' => $form->getValue('host'),
              'username' => $form->getValue('username'),
              'password' => $form->getValue('password'),
              'dbname' => $form->getValue('dbname'),
              'port' => $form->getValue('port'),
            );

            $databaseConfig['production']['database.type'] = 'pdo';
            $databaseConfig['development']['database.type'] = 'pdo';
            $databaseConfig['production']['database.adapter'] = 'PDO_MYSQL';
            $databaseConfig['development']['database.adapter'] = 'PDO_MYSQL';
            $databaseConfig['production']['database.params.host'] = $form->getValue('host');
            $databaseConfig['development']['database.params.host'] = $form->getValue('host');
            $databaseConfig['production']['database.params.username'] = $form->getValue('username');
            $databaseConfig['development']['database.params.username'] = $form->getValue('username');
            $databaseConfig['production']['database.params.password'] = $form->getValue('password');
            $databaseConfig['development']['database.params.password'] = $form->getValue('password');
            $databaseConfig['production']['database.params.dbname'] = $form->getValue('dbname');
            $databaseConfig['development']['database.params.dbname'] = $form->getValue('dbname');
            $databaseConfig['development']['database.params.port'] = $form->getValue('port');
            $databaseConfig['production']['database.params.port'] = $form->getValue('port');

            $db = Zend_Db::factory("PDO_MYSQL", $params);
            Zend_Db_Table::setDefaultAdapter($db);
            Zend_Registry::set('dbAdapter', $db);

            $dbtype = 'PDO_MYSQL';
            break;
          case 'pgsql':
            $this->Component->Utility->run_pgsql_from_file($sqlFile,
                                       $form->getValue('host'), $form->getValue('username'), $form->getValue('password'), $form->getValue('dbname'), $form->getValue('port'));
            $params = array(
              'host' => $form->getValue('host'),
              'username' => $form->getValue('username'),
              'password' => $form->getValue('password'),
              'dbname' => $form->getValue('dbname'),
              'port' => $form->getValue('port'),
            );

            $databaseConfig['production']['database.type'] = 'pdo';
            $databaseConfig['development']['database.type'] = 'pdo';
            $databaseConfig['production']['database.adapter'] = 'PDO_PGSQL';
            $databaseConfig['development']['database.adapter'] = 'PDO_PGSQL';
            $databaseConfig['production']['database.params.host'] = $form->getValue('host');
            $databaseConfig['development']['database.params.host'] = $form->getValue('host');
            $databaseConfig['production']['database.params.username'] = $form->getValue('username');
            $databaseConfig['development']['database.params.username'] = $form->getValue('username');
            $databaseConfig['production']['database.params.password'] = $form->getValue('password');
            $databaseConfig['development']['database.params.password'] = $form->getValue('password');
            $databaseConfig['production']['database.params.dbname'] = $form->getValue('dbname');
            $databaseConfig['development']['database.params.dbname'] = $form->getValue('dbname');
            $databaseConfig['development']['database.params.port'] = $form->getValue('port');
            $databaseConfig['production']['database.params.port'] = $form->getValue('port');

            $db = Zend_Db::factory("PDO_PGSQL", $params);
            Zend_Db_Table::setDefaultAdapter($db);
            Zend_Registry::set('dbAdapter', $db);
            $dbtype = 'PDO_PGSQL';
            break;
          default:
            break;
          }
        $databaseConfig['production']['version'] = str_replace('.sql', '', basename($sqlFile));
        $databaseConfig['development']['version'] = str_replace('.sql', '', basename($sqlFile));

        $this->Component->Utility->createInitFile(BASE_PATH.'/core/configs/database.local.ini', $databaseConfig);

        require_once BASE_PATH.'/core/controllers/components/UpgradeComponent.php';
        $upgradeComponent = new UpgradeComponent();
        $db = Zend_Registry::get('dbAdapter');

        $upgradeComponent->initUpgrade('core', $db, $dbtype);
        $upgradeComponent->upgrade(str_replace('.sql', '', basename($sqlFile)));

        $this->User = new UserModel(); //reset Database adapter
        $this->userSession->Dao = $this->User->createUser($form->getValue('email'), $form->getValue('userpassword1'),
                                $form->getValue('firstname'), $form->getValue('lastname'), 1);

        //create default assetstrore
        $assetstoreDao = new AssetstoreDao();
        $assetstoreDao->setName('Local');
        $assetstoreDao->setPath(BASE_PATH.'/data/assetstore');
        $assetstoreDao->setType(MIDAS_ASSETSTORE_LOCAL);
        $this->Assetstore = new AssetstoreModel(); //reset Database adapter
        $this->Assetstore->save($assetstoreDao);
        $this->_redirect("/install/step3");
        }
      }
    } // end method step2Action

  /**
   * @method step3Action()
   */
  function step3Action()
    {
    if(!file_exists(BASE_PATH."/core/configs/database.local.ini"))
      {
      $this->_redirect('/install/index');
      }
    $this->view->header = "Step3: Midas Configuration";
    $userDao = $this->userSession->Dao;
    if(!isset($userDao) || !$userDao->isAdmin())
      {
      unlink(BASE_PATH."/core/configs/database.local.ini");
      $this->_redirect('/install/index');
      }
    $applicationConfig = parse_ini_file(BASE_PATH.'/core/configs/application.ini', true);
    $form = $this->Form->Install->createConfigForm();
    $formArray = $this->getFormAsArray($form);
    $formArray['name']->setValue($applicationConfig['global']['application.name']);
    $formArray['keywords']->setValue($applicationConfig['global']['application.keywords']);
    $formArray['environment']->setValue($applicationConfig['global']['environment']);
    $formArray['lang']->setValue($applicationConfig['global']['application.lang']);
    $formArray['description']->setValue($applicationConfig['global']['application.description']);
    $formArray['smartoptimizer']->setValue($applicationConfig['global']['smartoptimizer']);
    $formArray['timezone']->setValue($applicationConfig['global']['default.timezone']);

    $assetstrores = $this->Assetstore->getAll();

    $this->view->form = $formArray;
    $this->view->databaseType = Zend_Registry::get('configDatabase')->database->adapter;
    if($this->_request->isPost() && $form->isValid($this->getRequest()->getPost()))
      {
      $allModules = $this->Component->Utility->getAllModules();
      foreach($allModules as $key => $module)
        {
        $configLocal = BASE_PATH."/core/configs/".$key.".local.ini";
        if(file_exists($configLocal))
          {
          unlink($configLocal);
          }
        }
      $applicationConfig['global']['application.name'] = $form->getValue('name');
      $applicationConfig['global']['application.description'] = $form->getValue('description');
      $applicationConfig['global']['application.keywords'] = $form->getValue('keywords');
      $applicationConfig['global']['application.lang'] = $form->getValue('lang');
      $applicationConfig['global']['environment'] = $form->getValue('environment');
      $applicationConfig['global']['defaultassetstore.id'] = $assetstrores[0]->getKey();
      $applicationConfig['global']['smartoptimizer'] = $form->getValue('smartoptimizer');
      $applicationConfig['global']['default.timezone'] = $form->getValue('timezone');

      $this->Component->Utility->createInitFile(BASE_PATH.'/core/configs/application.local.ini', $applicationConfig);
      $this->_redirect("/admin?checkRecentItem=true#tabs-modules");
      }
    } // end method step2Action


  /** ajax function which tests connectivity to a db */
  public function testconnexionAction()
    {
    $this->requireAjaxRequest();
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $type = $this->_getParam('type');
    $username = $this->_getParam('username');
    $password = $this->_getParam('password');
    $host = $this->_getParam('host');
    $dbname = $this->_getParam('dbname');
    $port = $this->_getParam('port');
    switch($type)
      {
      case 'mysql':
        $link = mysql_connect($host.":".$port, "$username", "$password");
        if(!$link)
          {
          $return = array(false, "Could not connect to the server '" . $host . "': ".mysql_error());
          break;
          }
        $dbcheck = mysql_select_db("$dbname");
        if(!$dbcheck)
          {
          $return = array(false, "Could not connect to the server '" . $host . "': ".mysql_error());
          break;
          }
        $sql = "SHOW TABLES FROM ".$dbname;
        $result = mysql_query($sql);
        if(mysql_num_rows($result) > 0)
          {
          $return = array(false, "The database is not empty");
          break;
          }
        $return = array(true, "The database is reachable");
        break;
      case 'pgsql':
        $link = pg_connect("host = ".$host." port = ".$port." dbname = ".$dbname." user = ".$username." password = ".$password);
        if(!$link)
          {
          $return = array(false, "Could not connect to the server '" . $host . "': ".pg_last_error($link));
          break;
          }
        $return = array(true, "The database is reachable");
        break;
      default:
        $return = array(false, "Database not defined");
        break;
      }
    echo JsonComponent::encode($return);
    }//end getElementInfo

} // end class

