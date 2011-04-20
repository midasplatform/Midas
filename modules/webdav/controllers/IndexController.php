<?php
require_once BASE_PATH.'/modules/webdav/library/SabreDAV/lib/Sabre/autoload.php';

/**
 * WebdavController
 * WebdavController
 */
class Webdav_IndexController extends Webdav_AppController
{

  public $_models=array('Item');
  public $_daos=array('Folder');
  public $_components=array();

  
  
  /**
   * @method initAction()
   *  Index Action (first action when we access the application)
   */
  function init()
    {         
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
     
   // Now we're creating a whole bunch of objects
    $fc = Zend_Controller_Front::getInstance();
    $webroot = $fc->getBaseUrl() ;
    
   /* // User authentication
    $auth = new Sabre_HTTP_BasicAuth();
    $result = $auth->getUserPass();
    
    if($result[0] != 'anonymous')
      {
      //$someone = $this->User->findByEmail($result[0]);  
      //if($someone['User']['password'] != md5($result[1]))
        {
        $auth->requireLogin();
        echo "Authentication required\n";
        die();
        }
      //$userid = $someone['User']['eperson_id'];  
      }
    else
      {
      $userid = 0;    
      }
    */
    // Change public to something else, if you are using a different directory for your files
    $rootDirectory = new Sabre_DAV_FS_Directory('C:/WEBS');
    
    // The server object is responsible for making sense out of the WebDAV protocol
    $server = new Sabre_DAV_Server($rootDirectory);
    
    // If your server is not on your webroot, make sure the following line has the correct information
    $server->setBaseUri($webroot.'/webdav/index');
    
    // $server->setBaseUri('/~evert/mydavfolder'); // if its in some kind of home directory
    // $server->setBaseUri('/dav/index.php/'); // if you can't use mod_rewrite, use index.php as a base uri
    // $server->setBaseUri('/'); // ideally, SabreDAV lives on a root directory with mod_rewrite sending every request to index.php
    
    // The lock manager is reponsible for making sure users don't overwrite each others changes. Change 'data' to a different 
    // directory, if you're storing your data somewhere else.
    $lockBackend = new Sabre_DAV_Locks_Backend_FS('C:/WEBS');
    $lockPlugin = new Sabre_DAV_Locks_Plugin($lockBackend);
    $browserPlugin = new Sabre_DAV_Browser_Plugin();
    $server->addPlugin(new Sabre_DAV_Mount_Plugin());
    $server->addPlugin($browserPlugin); 
    $server->addPlugin($lockPlugin);
    
     $tmpDir = $this->GetTempDirectory()."/webdav";
     
    // Temporary file filter (necessary for MacOS at least)
        $tempFF = new Sabre_DAV_TemporaryFileFilterPlugin($tmpDir);
        $server->addPlugin($tempFF);
        
    // All we need to do now, is to fire up the server
    $server->exec();
       
    exit(); // do not process anything else
    } // end method indexAction

  /*function indexAction()
  {
  } */ 
    
}//end class
  