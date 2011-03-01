<?php

class UploadController extends AppController
  {
  public $_models=array('User','Item','ItemRevision','Folder','Itempolicyuser','Itempolicygroup','Group','Feed',"Feedpolicygroup","Feedpolicyuser",'Bitstream','Assetstore');
  public $_daos=array('User','Item','ItemRevision','Bitstream','Folder');
  public $_components=array('Httpupload');
  public $_forms=array('Upload');

  /**
   * @method init()
   *  Init Controller
   */
  function init()
    {
    $maxFile=str_replace("M", "", ini_get('upload_max_filesize'));
    $maxPost=str_replace("M", "", ini_get('post_max_size'));
    if($maxFile<$maxPost)
      {
      $this->view->maxSizeFile=$maxFile;
      }
    else
      {
      $this->view->maxSizeFile=$maxPost;
      }
    }

    /** simple upload*/
    public function simpleuploadAction()
      {
      if(!$this->logged)
        {
        throw new Zend_Exception("You have to be logged in to do that");
        }
      if(!$this->getRequest()->isXmlHttpRequest())
        {
        throw new Zend_Exception("Error, should be an ajax action.");
        }
      $this->_helper->layout->disableLayout();
      $this->view->form=$this->getFormAsArray($this->Form->Upload->createUploadLinkForm());
      $this->userSession->uploaded=array();
      }//end simple upload


        /** save a link*/
    public function savelinkAction()
      {
      if(!$this->logged)
        {
        throw new Zend_Exception("You have to be logged in to do that");
        }
      if(!$this->getRequest()->isXmlHttpRequest())
        {
        throw new Zend_Exception("Error, should be an ajax action.");
        }
      $form=$this->Form->Upload->createUploadLinkForm();

      if(true)
        {
        $path_parts = pathinfo($form->getValue('url'));
        $name= $path_parts['basename'];
        $item = new ItemDao;
        $item->setName($name);
        $this->Item->save($item);
        $this->userSession->uploaded[]=$item->getKey();
        $feed=$this->Feed->createFeed($this->userSession->Dao,MIDAS_FEED_CREATE_LINK_ITEM,$item);
        $this->Folder->addItem($this->userSession->Dao->getPrivateFolder(),$item);
        $this->Feedpolicyuser->createPolicy($this->userSession->Dao,$feed,MIDAS_POLICY_ADMIN);
        $this->Itempolicyuser->createPolicy($this->userSession->Dao,$item,MIDAS_POLICY_ADMIN);
        $itemRevisionDao = new ItemRevisionDao;
        $itemRevisionDao->setChanges('Initial revision');
        $itemRevisionDao->setUser_id($this->userSession->Dao->getKey());
        $this->Item->addRevision($item,$itemRevisionDao);
        $this->getLogger()->info(__METHOD__." Upload link ok $name:".$form->getValue('url'));
        }
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      }//end simple upload

    /** java upload*/
    public function javauploadAction()
      {
      if(!$this->logged)
        {
        throw new Zend_Exception("You have to be logged in to do that");
        }
      if(!$this->getRequest()->isXmlHttpRequest())
        {
        throw new Zend_Exception("Error, should be an ajax action.");
        }
      $this->_helper->layout->disableLayout();
      $this->view->protocol="http";
      $this->view->host=empty($_SERVER['HTTP_X_FORWARDED_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['HTTP_X_FORWARDED_HOST'];
      }//end java upload


   /** used to see how much of a file made it to the server during an
   * interrupted upload attempt **/
  function gethttpuploadoffsetAction()
    {
    $params=$this->_getAllParams();
    $url=$this->view->url();
    $url=substr($url,  0,strrpos($url, '/'));
    $params['internParameter']= substr($url, strrpos($url, '/')+1);
    $this->Component->Httpupload->get_http_upload_offset($params);
    } //end get_http_upload_offset

    /** java upload function, didn 't check what it does :-) */
  function gethttpuploaduniqueidentifierAction()
    {
    $params=$this->_getAllParams();
    $this->Component->Httpupload->get_http_upload_unique_identifier($params);
    } //end get_http_upload_unique_identifier


    /** process java upload*/
  function processjavauploadAction()
    {
    $params=$this->_getAllParams();
    if(!$this->logged)
      {
      throw new Zend_Exception("You have to be logged in to do that");
      }
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $TMP_DIR = BASE_PATH.'/tmp/misc/';
    list ($filename, $path, $length) = $this->Component->Httpupload->process_http_upload($params);

    if (!empty($path)&& file_exists($path) &&$length > 0)
      {
      $item=$this->createUploadedItem($this->userSession->Dao,$filename,$path);
      $this->userSession->uploaded[]=$item->getKey();
      }
    } //end processjavaupload


    /** save an uploaded file */
    public function saveuploadedAction()
      {
      if(!$this->logged)
        {
        throw new Zend_Exception("You have to be logged in to do that");
        }
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $upload = new Zend_File_Transfer();
      $upload->receive();
      $path=$upload->getFileName();
      $privacy=$this->_getParam("privacy");

      if (!empty($path)&& file_exists($path) && $upload->getFileSize() > 0)
        {
        $item=$this->createUploadedItem($this->userSession->Dao,$upload->getFilename(null,false),$upload->getFilename(),$privacy);
        $this->userSession->uploaded[]=$item->getKey();
        }
      }//end saveuploaded

      /** save upload item in the DB */
    private function createUploadedItem($userDao,$name,$path,$privacy=null)
      {
      $item = new ItemDao;
      $item->setName($name);
      $this->Item->save($item);
      $feed=$this->Feed->createFeed($this->userSession->Dao,MIDAS_FEED_CREATE_ITEM,$item);
      if(isset($privacy)&&$privacy=='public')
        {
        $this->Folder->addItem($userDao->getPublicFolder(),$item);
        $anonymousGroup=$this->Group->load(MIDAS_GROUP_ANONYMOUS_KEY);
        $this->Itempolicygroup->createPolicy($anonymousGroup,$item,MIDAS_POLICY_READ);
        $this->Feedpolicygroup->createPolicy($anonymousGroup,$feed,MIDAS_POLICY_READ);
        }
      else
        {
        $this->Folder->addItem($userDao->getPrivateFolder(),$item);
        }
      $this->Feedpolicyuser->createPolicy($this->userSession->Dao,$feed,MIDAS_POLICY_ADMIN);
      $this->Itempolicyuser->createPolicy($userDao,$item,MIDAS_POLICY_ADMIN);
      $itemRevisionDao = new ItemRevisionDao;
      $itemRevisionDao->setChanges('Initial revision');
      $itemRevisionDao->setUser_id($userDao->getKey());
      $this->Item->addRevision($item,$itemRevisionDao);
     
      $defaultAssetStoreId=Zend_Registry::get('configGlobal')->defaultassetstore->id;
      $assetstoreDao=$this->Assetstore->load($defaultAssetStoreId);
      
      $bitstreamDao=$this->Bitstream->initBitstream($assetstoreDao, $name, $path);
      $this->ItemRevision->addBitstream($itemRevisionDao,$bitstreamDao);

      $this->getLogger()->info(__METHOD__." Upload ok (".$privacy."):".$path);
      return $item;
      }//end createUploadedItem

  }//end class

  ?>