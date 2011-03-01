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

    $this->view->title=Zend_Registry::get('configGlobal')->application->name;

    // Set the version
    $this->view->version='3.0 beta';
    // Init the translater
    if (!$this->isDebug())
      {
      $frontendOptions=array(
        'lifetime'=>86400,'automatic_serialization'=>true
      );

      $backendOptions=array(
        'cache_dir'=>BASE_PATH.'/tmp/cache/translation'
      );
      $cache=Zend_Cache::factory('Core','File',$frontendOptions,$backendOptions);
      Zend_Translate::setCache($cache);
      }
    $translate=new Zend_Translate('csv',BASE_PATH.'/translation/fr-main.csv','en');

    Zend_Registry::set('translater',$translate);

    //Init Session
    $user=new Zend_Session_Namespace('Auth_User');
    if (!isset($user->initialized))
      {
      Zend_Session::regenerateId();
      $user->initialized=true;
      }
    $user->setExpirationSeconds(60*Zend_Registry::get('configGlobal')->session->lifetime);
    $this->userSession=$user;
    if ($user->Dao!=null)
      {
      $this->logged=true;
      $this->view->logged=true;
      $this->view->userDao=$user->Dao;
      }
    else
      {
      $this->view->logged=false;
      $this->logged=false;
      }
    $this->view->recentItems=$user->recentItems;
    $this->view->lang=Zend_Registry::get('configGlobal')->application->lang;
   //create a global javascript json array
    $jsonGlobal=array(
      "webroot"=>$this->view->webroot,
      "logged"=>$this->logged,
      "currentUri"=>$this->getRequest()->REQUEST_URI,
      "lang"=>Zend_Registry::get('configGlobal')->application->lang
    );
    $login=array(
      "titleUploadLogin"=>$this->t('Please log in'),
      "contentUploadLogin"=>utf8_encode($this->t('You need to be logged in to be able to upload files.'))
    );

    $this->view->json=array(
      "global"=>$jsonGlobal,"login"=>$login
    );
    Zend_Loader::loadClass("JsonComponent",BASE_PATH.'/application/controllers/components');
    } // end preDispatch()


  public function postDispatch()
    {
    parent::postDispatch();
    $this->view->json=JsonComponent::encode($this->view->json);
    if (Zend_Registry::get('config')->mode->test!=1)
      {
      header('Content-Type: text/html; charset=ISO-8859-1');
      }
    }

  /** translation */
  protected function t($text)
    {
    if (Zend_Registry::get('configGlobal')->application->lang=='fr')
      {
      $translate=Zend_Registry::get('translater');
      $new_text=$translate->_($text);
      if ($new_text==$text&&Zend_Registry::get('config')->mode->debug==1)
        {
        $content=@file_get_contents(BASE_PATH."/tmp/report/translation-fr.csv");
        if (strpos($content,$text.";")==false)
          {
          $translationFile=BASE_PATH."/tmp/report/translation-fr.csv";
          $fh=fopen($translationFile,'a');
          fwrite($fh,"\n$text;");
          fclose($fh);
          }
        }
      return $new_text;
      }
    return $text;
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