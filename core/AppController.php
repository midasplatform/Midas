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
    $this->view->webroot=$fc->getBaseUrl();
    $this->coreWebroot=$this->view->webroot.'/core';
    $this->view->coreWebroot=$this->coreWebroot;

    $this->view->title=Zend_Registry::get('configGlobal')->application->name;

    // Set the version
    $this->view->version='3.0 beta';
    //Init Session
    if($fc->getRequest()->getActionName()!='login'||$fc->getRequest()->getControllerName()!='user')
      {
      Zend_Loader::loadClass( "UserDao", BASE_PATH . '/core/models/dao');
      Zend_Loader::loadClass( "ItemDao", BASE_PATH . '/core/models/dao');

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
        $cookieData =  $this->getRequest()->getCookie('recentItems'.$this->userSession->Dao->getKey());
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
      'preview'=>$this->t('Preview'),
      'download'=>$this->t('Download'),
      'manage'=>$this->t('Manage'),
      'edit'=>$this->t('Edit'),
      'delete'=>$this->t('Delete'),
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

  /** translation */
  protected function t($text)
    {
    Zend_Loader::loadClass("InternationalizationComponent",BASE_PATH.'/core/controllers/components');
    return InternationalizationComponent::translate($text);
    } //end method t

  /**completion eclipse*/
  /**
   * Assetstrore Model
   * @var AssetstoreModel
   */
  var $Assetstore;
  /**
   * Bitstream Model
   * @var BitstreamModel
   */
  var $Bitstream;
  /**
   * Community Model
   * @var CommunityModel
   */
  var $Community;
  /**
   * Feed Model
   * @var FeedModel
   */
  var $Feed;
  /**
   * Feedpolicygroup Model
   * @var FeedpolicygroupModel
   */
  var $Feedpolicygroup;
    /**
   * Feedpolicyuser Model
   * @var FeedpolicyuserModel
   */
  var $Feedpolicyuser;
  /**
   * Folder Model
   * @var FolderModel
   */
  var $Folder;
  /**
   * Folderpolicygroup Model
   * @var FolderpolicygroupModel
   */
  var $Folderpolicygroup;
    /**
   * Folderpolicyuser Model
   * @var FolderpolicyuserModel
   */
  var $Folderpolicyuser;
  /**
   * Group Model
   * @var GroupModel
   */
  var $Group;
   /**
   * ItemKeyword Model
   * @var ItemKeywordModel
   */
  var $ItemKeyword;
  /**
   * Item Model
   * @var ItemModel
   */
  var $Item;
  /**
   * Itempolicygroup Model
   * @var ItempolicygroupModel
   */
  var $Itempolicygroup;
    /**
   * Itempolicyuser Model
   * @var ItempolicyuserModel
   */
  var $Itempolicyuser;
  /**
   * ItemRevision Model
   * @var ItemRevisionModel
   */
  var $ItemRevision;
    /**
   * User Model
   * @var UserModel
   */
  var $User;
  
  /**end completion eclipse */
  }

  //end class
?>