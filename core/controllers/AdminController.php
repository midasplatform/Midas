<?php

/**
 *  AJAX request for the admin Controller
 */
class AdminController extends AppController
{
  public $_models=array();
  public $_daos=array();
  public $_components=array('Upgrade');
    
  /** index*/
  function indexAction()
    {
    
    }//end indexAction
    
   
  /** upgrade database*/
  function upgradeAction()
    {
    if(!$this->logged||!$this->userSession->Dao->getAdmin()==1)
      {
      throw new Zend_Exception("You should be an administrator");
      }

    $db=Zend_Registry::get('dbAdapter');
    $dbtype=Zend_Registry::get('configDatabase')->database->adapter;
    $modulesConfig=Zend_Registry::get('configsModules');
    
    if($this->_request->isPost())
      {
      $upgraded=false;
      $modulesConfig=Zend_Registry::get('configsModules');
      $modules=array();
      foreach($modulesConfig as $key=>$module)
        {
        $this->Component->Upgrade->init($key,$db,$dbtype);
        $upgraded=$upgraded||$this->Component->Upgrade->upgrade($module->version);
        }    
      $this->Component->Upgrade->init('core',$db,$dbtype);
      $upgraded=$upgraded||$this->Component->Upgrade->upgrade(Zend_Registry::get('configDatabase')->version);
      $this->view->upgraded=$upgraded;
      
      $dbtype=Zend_Registry::get('configDatabase')->database->adapter;
      $modulesConfig=Zend_Registry::get('configsModules');
      }
      
    $modules=array();
    foreach($modulesConfig as $key=>$module)
      {
      $this->Component->Upgrade->init($key,$db,$dbtype);
      $modules[$key]['target']=$this->Component->Upgrade->getNewestVersion();
      $modules[$key]['targetText']=$this->Component->Upgrade->getNewestVersion(true);
      $modules[$key]['currentText']=$module->version;
      $modules[$key]['current']=$this->Component->Upgrade->transformVersionToNumeric($module->version);
      }      
   
    $this->view->modules=$modules;
    
    $this->Component->Upgrade->init('core',$db,$dbtype);
    $core['target']=$this->Component->Upgrade->getNewestVersion();
    $core['targetText']=$this->Component->Upgrade->getNewestVersion(true);
    $core['currentText']=Zend_Registry::get('configDatabase')->version;
    $core['current']=$this->Component->Upgrade->transformVersionToNumeric(Zend_Registry::get('configDatabase')->version);
    $this->view->core=$core;
    }//end upgradeAction
    
  /**
   * \fn serversidefilechooser()
   * \brief called by the server-side file chooser
   */
  function serversidefilechooserAction()
    {
    /*$userid = $this->CheckSession();
    if (!$this->User->isAdmin($userid))
      {
      echo "Administrative privileges required";
      exit ();
      }
      */
    
    // Display the tree
    $_POST['dir'] = urldecode($_POST['dir']);
    $files = array();
    if( strpos( strtolower(PHP_OS), 'win') !== false )
      {
      $files = array();
      for($c='A'; $c<='Z'; $c++)
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

    if( file_exists($_POST['dir']) || file_exists($files[0]) ) 
      {
      if(file_exists($_POST['dir']))
        {
        $files = scandir($_POST['dir']);
        }
      natcasesort($files);
      echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
      foreach( $files as $file ) 
        {
        if( file_exists( $_POST['dir'] . $file) && $file != '.' && $file != '..' && is_readable($_POST['dir'] . $file) )
          {
          if( is_dir($_POST['dir'] . $file) )
            {
            echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "/\">" . htmlentities($file) . "</a></li>";  
            }
          else // not a directory: a file!
            {
            $ext = preg_replace('/^.*\./', '', $file); 
            echo "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "\">" . htmlentities($file) . "</a></li>";
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
    exit();
    } // end function  serversidefilechooserAction
    
    
    
    
    
} // end class

  