<?php

/**
 *  InstallController
 */
class InstallController extends AppController
{
  public $_models=array('User','Assetstore');
  public $_daos=array('Assetstore');
  public $_components=array('Utility');
  public $_forms=array('Install');
    
  /**
   * @method init()
   */
  function init()
    {
    if(file_exists(BASE_PATH."/application/configs/database.local.ini")&&file_exists(BASE_PATH."/application/configs/application.local.ini"))
      {
      throw new Zend_Exception("Midas is already installed.");
      }
    } //end init

  /**
   * @method indexAction()
   */
  function indexAction()
    {
    if(file_exists(BASE_PATH."/application/configs/database.local.ini"))
      {
      $this->_redirect('/install/step3');
      }
    $this->view->header="Step1: Server Configuration";
        // Check PHP extension / function
    $phpextensions = array (
      "curl_init"  => array(false, "Certain features, such as statistics won't be available. It is recommended to enable/install cURL."),
      "openssl"    => array(false, "Bitstreams signature won't be available. It is recommended to enable/install OpenSSL."),
      "simplexml"  => array(false, ""), 
    );
    $this->view->phpextension_missing=$this->Component->Utility->CheckPhpExtensions($phpextensions);
    $this->view->writable=is_writable(BASE_PATH.'/application/configs');  
    $this->view->convertfound=$this->Component->Utility->IsImageMagickWorking();   
    $this->view->basePath=BASE_PATH;
    setcookie("recentItems", '', time()+60*60*24*30,'/'); //30 days
    if(!empty($_POST)&&$this->view->writable)
      {
      $this->_redirect("/install/step2");
      }
    } // end method indexAction   
    
    
    /**
   * @method step2Action()
   */
  function step2Action()
    {
    if(file_exists(BASE_PATH."/application/configs/database.local.ini"))
      {
      $this->_redirect('/install/step3');
      }
    $this->view->header="Step2: Database Configuration";
        // Check PHP extension / function
    $phpextensions = array (
      "mysql"  => array(false, ''),
      "pgsql"    => array(false, ''),
      "oci" => array(false, ''),
      "sqlite" => array(false, ''),
      "ibm" => array(false, ''),
    );
    
    $this->view->databaseType=array();
    
    foreach($phpextensions as $key => $t)
      {      
      if(!file_exists(BASE_PATH."/sql/{$key}/{$this->view->version}.sql"))
        {
        unset($phpextensions[$key]);
        }
      else
        {
        $this->view->databaseType[$key]=$this->getFormAsArray($this->Form->Install->createDBForm($key));
        }
      }
    $this->view->phpextension_missing=$this->Component->Utility->CheckPhpExtensions($phpextensions);
    $this->view->writable=is_writable(BASE_PATH);  
    $this->view->convertfound=$this->Component->Utility->IsImageMagickWorking();   
    $this->view->basePath=BASE_PATH;
    
    if($this->_request->isPost())
      {
      $type=$this->_getParam('type');      
      $form=$this->Form->Install->createDBForm($type);
      if($form->isValid($this->getRequest()->getPost()))
        {
        $databaseConfig=parse_ini_file (BASE_PATH.'/application/configs/database.ini',true);
        switch($type)
          {
          case 'mysql':
            $this->run_mysql_from_file(BASE_PATH."/sql/{$type}/{$this->view->version}.sql",
                                       $form->getValue('host'), $form->getValue('username'), $form->getValue('password'), $form->getValue('dbname'));
              $params= array(
                'host' => $form->getValue('host'),
                'username' => $form->getValue('username'),
                'password' => $form->getValue('password'),
                'dbname' => $form->getValue('dbname'),
              );
              
              $databaseConfig['production']['database.type']='pdo';
              $databaseConfig['development']['database.type']='pdo';
              $databaseConfig['production']['database.adapter']='PDO_MYSQL';
              $databaseConfig['development']['database.adapter']='PDO_MYSQL';
              $databaseConfig['production']['database.params.host']=$form->getValue('host');
              $databaseConfig['development']['database.params.host']=$form->getValue('host');
              $databaseConfig['production']['database.params.username']=$form->getValue('username');
              $databaseConfig['development']['database.params.username']=$form->getValue('username');
              $databaseConfig['production']['database.params.password']=$form->getValue('password');
              $databaseConfig['development']['database.params.password']=$form->getValue('password');
              $databaseConfig['production']['database.params.dbname']=$form->getValue('dbname');
              $databaseConfig['development']['database.params.dbname']=$form->getValue('dbname');

              $db = Zend_Db::factory("PDO_MYSQL",$params);
              Zend_Db_Table::setDefaultAdapter($db);
              Zend_Registry::set('dbAdapter', $db);
            break;
         case 'pgsql':
            $this->run_pgsql_from_file(BASE_PATH."/sql/{$type}/{$this->view->version}.sql",
                                       $form->getValue('host'), $form->getValue('username'), $form->getValue('password'), $form->getValue('dbname'));
              $params= array(
                'host' => $form->getValue('host'),
                'username' => $form->getValue('username'),
                'password' => $form->getValue('password'),
                'dbname' => $form->getValue('dbname'),
              );
              
              $databaseConfig['production']['database.type']='pdo';
              $databaseConfig['development']['database.type']='pdo';
              $databaseConfig['production']['database.adapter']='PDO_PGSQL';
              $databaseConfig['development']['database.adapter']='PDO_PGSQL';
              $databaseConfig['production']['database.params.host']=$form->getValue('host');
              $databaseConfig['development']['database.params.host']=$form->getValue('host');
              $databaseConfig['production']['database.params.username']=$form->getValue('username');
              $databaseConfig['development']['database.params.username']=$form->getValue('username');
              $databaseConfig['production']['database.params.password']=$form->getValue('password');
              $databaseConfig['development']['database.params.password']=$form->getValue('password');
              $databaseConfig['production']['database.params.dbname']=$form->getValue('dbname');
              $databaseConfig['development']['database.params.dbname']=$form->getValue('dbname');

              $db = Zend_Db::factory("PDO_PGSQL",$params);
              Zend_Db_Table::setDefaultAdapter($db);
              Zend_Registry::set('dbAdapter', $db);
            break;
          default:
            break;
          }
        $this->Component->Utility->createInitFile(BASE_PATH.'/application/configs/database.local.ini',$databaseConfig);
        $this->User=new UserModel(); //reset Database adapter
        $this->userSession->Dao=$this->User->createUser($form->getValue('email'),$form->getValue('userpassword1'),
                                $form->getValue('firstname'),$form->getValue('lastname'),1);
        
        //create default assetstrore
        $assetstoreDao = new AssetstoreDao();
        $assetstoreDao->setName('Default');
        $assetstoreDao->setPath(BASE_PATH.'/data/assetstore');
        $assetstoreDao->setType(MIDAS_ASSETSTORE_LOCAL);
        $this->Assetstore=new AssetstoreModel(); //reset Database adapter
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
    if(!file_exists(BASE_PATH."/application/configs/database.local.ini"))
      {
      $this->_redirect('/install/index');
      }
    $this->view->header="Step3: Midas Configuration";
    $userDao=$this->userSession->Dao;
    if(!$userDao->isAdmin())
      {
      throw new Zend_Exception("You should be an admin.");
      }
    $applicationConfig=parse_ini_file (BASE_PATH.'/application/configs/application.ini',true);
    $form=$this->Form->Install->createConfigForm();
    $formArray=$this->getFormAsArray($form);
    $formArray['name']->setValue($applicationConfig['global']['application.name']);
    $formArray['environment']->setValue($applicationConfig['global']['environment']);
    $formArray['lang']->setValue($applicationConfig['global']['application.lang']);
    $formArray['smartoptimizer']->setValue($applicationConfig['global']['smartoptimizer']);
    $formArray['timezone']->setValue($applicationConfig['global']['default.timezone']);
    $formArray['process']->setValue($applicationConfig['global']['processing']);
 
    $assetstrores=$this->Assetstore->getAll();
    $formArray['assetstore']->addMultiOptions(array(
                    $assetstrores[0]->getKey() => $assetstrores[0]->getPath()                  
                        ));    
    
    $this->view->form=$formArray;
    
    if($this->_request->isPost()&&$form->isValid($this->getRequest()->getPost()))
      {
      $applicationConfig['global']['application.name']=$form->getValue('name');
      $applicationConfig['global']['application.lang']=$form->getValue('lang');
      $applicationConfig['global']['environment']=$form->getValue('environment');
      $applicationConfig['global']['defaultassetstore.id']=$form->getValue('assetstore');
      $applicationConfig['global']['smartoptimizer']=$form->getValue('smartoptimizer');
      $applicationConfig['global']['default.timezone']=$form->getValue('timezone');
      $applicationConfig['global']['processing']=$form->getValue('process');
      $this->Component->Utility->createInitFile(BASE_PATH.'/application/configs/application.local.ini',$applicationConfig);
      $this->_redirect("/");
      }
    } // end method step2Action   
    
  /** ajax function which tests connectivity to a db */
  public function testconnexionAction()
    {
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception("Why are you here ? Should be ajax.");
      }
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $type=$this->_getParam('type');
    $username=$this->_getParam('username');
    $password=$this->_getParam('password');
    $host=$this->_getParam('host');
    $dbname=$this->_getParam('dbname');
    switch ($type)
      {
      case 'mysql':
        $link = @mysql_connect("$host", "$username", "$password");
        if (!$link) 
          {
          $return= array(false, "Could not connect to the server '" . $host . "': ".mysql_error());
          break;
          }
        $dbcheck = mysql_select_db("$dbname");
        if (!$dbcheck) 
          {
          $return= array(false, "Could not connect to the server '" . $host . "': ".mysql_error());
          break;
          }
        $sql = "SHOW TABLES FROM $dbname";
        $result = mysql_query($sql);
        if (mysql_num_rows($result) > 0)
          {
          $return= array(false, "The database is not empty");
          break;
          }
        $return =array(true,"The database is reachable");
        break;
      case 'pgsql':
        $link = @pg_connect("host=$host port=5432 dbname=$dbname user=$username password=$password");
        if (!$link) 
          {
          $return= array(false, "Could not connect to the server '" . $host . "': ".pg_last_error($link));
          break;
          }     
        $return =array(true,"The database is reachable");
        break; 
      default:
        $return = array(false,"Database not defined");
        break;
      }
    echo JsonComponent::encode($return);
    }//end getElementInfo

    
    
          /** Function to run the sql script */
  function run_mysql_from_file($sqlfile,$host,$username,$password,$dbname)
    {
    $db = @mysql_connect("$host", "$username", "$password");
    $select=@mysql_select_db($dbname,$db);
    if(!$db||!$select)
      {
      throw new Zend_Exception("Unable to connect.");
      }
    $requetes="";

    $sql=file($sqlfile); 
    foreach($sql as $l)
      {
      if (substr(trim($l),0,2)!="--")
        { 
        $requetes .= $l;
        }
      }

    $reqs = explode(";",$requetes);
    foreach($reqs as $req)
      {	// et on les éxécute
      if (!mysql_query($req,$db) && trim($req)!="")
        {
        throw new Zend_Exception("Unable to execute: ".$req );
        }
      }
    return true;
    }
      /** Function to run the sql script */
  function run_pgsql_from_file($sqlfile,$host,$username,$password,$dbname)
    {
    $pgdb = @pg_connect("host=$host port=5432 dbname=$dbname user=$username password=$password");
    $file_content = file($sqlfile);
    $query = "";
    $linnum = 0;
    foreach ($file_content as $sql_line)
      {
      $tsl = trim($sql_line);
      if (($sql_line != "") && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != "#"))
        {
        $query .= $sql_line;
        if (preg_match("/;\s*$/", $sql_line))
          {
          $query = str_replace(";", "", "$query");
          $result = pg_query($query);
          if (!$result)
            {
            echo "Error line:".$linnum."<br>";
            return pg_last_error();
            }
          $query = "";
          }
        }
      $linnum++;
      } // end for each line
    return true;
    }
    
} // end class

  