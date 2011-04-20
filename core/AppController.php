<?php

/**
 * GlobalAction
 * Provides global function to the controllers
 */
class AppController extends MIDAS_GlobalController
  {
  /**
   * Pre-dispatch routines
   *
   * @return void
   */
  public function preDispatch()
    {
    parent::preDispatch();
    $this->view->setEncoding('iso-8859-1');
    
    $fc=Zend_Controller_Front::getInstance();
    $module=$fc->getRequest()->getModuleName();
    if($module=='default')
      {
      $module='core';
      }
    $this->getLogger()->setEventItem('module',$module);
    $this->view->webroot=$fc->getBaseUrl();
    $this->coreWebroot=$this->view->webroot.'/core';
    $this->view->coreWebroot=$this->coreWebroot;

    $this->view->title=Zend_Registry::get('configGlobal')->application->name;

    // Set the version
    $this->view->version='3.0.0';
    if(isset(Zend_Registry::get('configDatabase')->version))
      {
      $this->view->version=Zend_Registry::get('configDatabase')->version;
      }
    
    require_once BASE_PATH . '/core/models/dao/UserDao.php';
    require_once BASE_PATH . '/core/models/dao/ItemDao.php';
    //Init Session
    if($fc->getRequest()->getActionName()!='login'||$fc->getRequest()->getControllerName()!='user')
      {


      if (isset($_POST['sid']))    
        { 
        Zend_Session::setId($_POST['sid']);       
        }
      Zend_Session::start();  
      $user=new Zend_Session_Namespace('Auth_User');
      $user->setExpirationSeconds(60*Zend_Registry::get('configGlobal')->session->lifetime);
      $this->userSession=$user;
      $this->view->recentItems=array();
      if ($user->Dao!=null)
        {
        $this->logged=true;
        $this->view->logged=true;
        $this->view->userDao=$user->Dao;
        $cookieData =  $this->getRequest()->getCookie('recentItems'.$this->userSession->Dao->user_id);
        $this->view->recentItems=array();
        if(isset($cookieData))
          {
          $this->view->recentItems= unserialize($cookieData); 
          } 
        }
      else
        {
        $this->view->logged=false;
        $this->logged=false;
        }
      }
    else
      {
      $this->userSession=null;
      $this->view->logged=false;
      $this->logged=false;
      }
      
    $this->view->lang=Zend_Registry::get('configGlobal')->application->lang;
   //create a global javascript json array
    $jsonGlobal=array(
      "webroot"=>$this->view->webroot,
      "coreWebroot"=>$this->view->coreWebroot,
      "logged"=>$this->logged,
      "needToLog"=>false,
      "currentUri"=>$this->getRequest()->REQUEST_URI,
      "lang"=>Zend_Registry::get('configGlobal')->application->lang,
      "Yes"=>$this->t('Yes'),
      "No"=>$this->t('No')
    );
    $login=array(
      "titleUploadLogin"=>$this->t('Please log in'),
      "contentUploadLogin"=>$this->t('You need to be logged in to be able to upload files.')
    );
    
    $browse=array(
      'view'=>$this->t('View'),
      'createFolder'=>$this->t('Create a new Folder'),
      'preview'=>$this->t('Preview'),
      'download'=>$this->t('Download'),
      'manage'=>$this->t('Manage'),
      'edit'=>$this->t('Edit'),
      'delete'=>$this->t('Delete'),
      'deleteMessage'=>$this->t('Do you really want to delete the folder'),
      'share'=>$this->t('Share'),
      'rename'=>$this->t('Rename'),
      'move'=>$this->t('Move'),
      'copy'=>$this->t('Copy'),
      'element'=>$this->t('element'),
      'community' => array(
          'invit'=>$this->t('Invite collaborators'),
          'advanced'=>$this->t('Advanced properties'),
          )
    );
      
    $feed=array(
      "deleteFeed"=>$this->t('Do you really want to delete the feed')
    );

    $this->view->json=array(
      "global"=>$jsonGlobal,"login"=>$login,'feed'=>$feed,"browse"=>$browse
    );
    Zend_Loader::loadClass("JsonComponent",BASE_PATH.'/core/controllers/components');
    } // end preDispatch()


  public function postDispatch()
    {
    parent::postDispatch();
    $this->view->json=JsonComponent::encode($this->view->json);
    $this->view->generatedTimer= round((microtime(true) - START_TIME),3);
    if (Zend_Registry::get('config')->mode->test!=1)
      {
      header('Content-Type: text/html; charset=ISO-8859-1');
      }
    }

  public function haveToBeLogged()
    {
    $this->view->header=$this->t("You should be logged to access this page");
    $this->view->json['global']['needToLog']=true;
    $this->_helper->viewRenderer->setNoRender();
    }
  /** translation */
  protected function t($text)
    {
    Zend_Loader::loadClass("InternationalizationComponent",BASE_PATH.'/core/controllers/components');
    return InternationalizationComponent::translate($text);
    } //end method t

  /**completion eclipse*/
  /**
   * Assetstrore Model
   * @var AssetstoreModelBase
   */
  var $Assetstore;
  /**
   * Bitstream Model
   * @var BitstreamModelBase
   */
  var $Bitstream;
  /**
   * Community Model
   * @var CommunityModelBase
   */
  var $Community;
  /**
   * Errorlog Model
   * @var ErrorlogModelBase
   */
  var $Errorlog;
  /**
   * Feed Model
   * @var FeedModelBase
   */
  var $Feed;
  /**
   * Feedpolicygroup Model
   * @var FeedpolicygroupModelBase
   */
  var $Feedpolicygroup;
    /**
   * Feedpolicyuser Model
   * @var FeedpolicyuserModelBase
   */
  var $Feedpolicyuser;
  /**
   * Folder Model
   * @var FolderModelBase
   */
  var $Folder;
  /**
   * Folderpolicygroup Model
   * @var FolderpolicygroupModelBase
   */
  var $Folderpolicygroup;
    /**
   * Folderpolicyuser Model
   * @var FolderpolicyuserModelBase
   */
  var $Folderpolicyuser;
  /**
   * Group Model
   * @var GroupModelBase
   */
  var $Group;
   /**
   * ItemKeyword Model
   * @var ItemKeywordModelBase
   */
  var $ItemKeyword;
  /**
   * Item Model
   * @var ItemModelBase
   */
  var $Item;
  /**
   * Itempolicygroup Model
   * @var ItempolicygroupModelBase
   */
  var $Itempolicygroup;
    /**
   * Itempolicyuser Model
   * @var ItempolicyuserModelBase
   */
  var $Itempolicyuser;
  /**
   * ItemRevision Model
   * @var ItemRevisionModelBase
   */
  var $ItemRevision;
    /**
   * User Model
   * @var UserModelBase
   */
  var $User;
  
  /**end completion eclipse */
  }

  //end class
?>