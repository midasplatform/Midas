<?php

class Remoteprocessing_JobController extends Remoteprocessing_AppController
{
  public $_models = array('Item', 'Bitstream', 'ItemRevision', 'Assetstore');
  public $_components = array('Upload');
  public $_moduleComponents = array('Executable');

  /** manage jobs */
  function manageAction()
    {

    $itemId = $this->_getParam("itemId");
    if(!isset($itemId) || !is_numeric($itemId))
      {
      throw new Zend_Exception("itemId  should be a number");
      }

    $itemDao = $this->Item->load($itemId);
    if($itemDao === false)
      {
      throw new Zend_Exception("This item doesn't exist.");
      }
    if(!$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Problem policies.");
      }
    $this->view->header = $this->t("Manage Jobs: ".$itemDao->getName());
    $metaFile = $this->ModuleComponent->Executable->getMetaIoFile($itemDao);
    $this->view->metaFile = $metaFile;
    $this->view->itemDao = $itemDao;
    }

   /** init a job */
  function initAction()
    {
    $this->view->header = $this->t("Job");
    $itemId = $this->_getParam("itemId");
    if(!isset($itemId) || !is_numeric($itemId))
      {
      throw new Zend_Exception("itemId  should be a number");
      }

    $itemDao = $this->Item->load($itemId);
    if($itemDao === false)
      {
      throw new Zend_Exception("This item doesn't exist.");
      }
    if(!$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Problem policies.");
      }

    $metaFile = $this->ModuleComponent->Executable->getMetaIoFile($itemDao);
    if($metaFile == false)
      {
      $this->_redirect('/remoteprocessing/executable/define?init=false&itemId='.$itemDao->getKey());
      return;
      }

    $metaContent = new SimpleXMLElement(file_get_contents($metaFile->getFullPath()));
    $this->view->metaContent = $metaContent;

    $this->view->itemDao = $itemDao;
    $this->view->json['item'] = $itemDao->toArray();
     if($this->_request->isPost())
      {
      $this->disableLayout();
      $this->disableView();

      $this->ModuleComponent->Executable->initAndSchedule($itemDao, $metaContent, $_POST['results']);
      /*

      $results = $_POST['results'];
      $xmlContent = $this->ModuleComponent->Executable->createDefinitionFile($results);
      $pathFile = BASE_PATH.'/tmp/misc/'.uniqid().time();
      file_put_contents($pathFile, $xmlContent);

      $revision = $this->Item->getLastRevision($itemDao);
      $bitstreams = $revision->getBitstreams();

      $itemRevisionDao = new ItemRevisionDao;
      $itemRevisionDao->setChanges('Modification Definition File');
      $itemRevisionDao->setUser_id($this->userSession->Dao->getKey());
      $itemRevisionDao->setDate(date('c'));
      $itemRevisionDao->setLicense(null);
      $this->Item->addRevision($itemDao, $itemRevisionDao);

      foreach($bitstreams as $b)
        {
        if($b->getName() != 'MetaIO.vxml')
          {
          $b->saved = false;
          $b->setBitstreamId(null);
          $this->Bitstream->save($b);
          $this->ItemRevision->addBitstream($itemRevisionDao, $b);
          }
        }

      $bitstreamDao = new BitstreamDao;
      $bitstreamDao->setName('MetaIO.vxml');
      $bitstreamDao->setPath($pathFile);
      $bitstreamDao->fillPropertiesFromPath();
      $defaultAssetStoreId = Zend_Registry::get('configGlobal')->defaultassetstore->id;
      $bitstreamDao->setAssetstoreId($defaultAssetStoreId);
      $assetstoreDao = $this->Assetstore->load($defaultAssetStoreId);

      // Upload the bitstream if necessary (based on the assetstore type)
      $this->Component->Upload->uploadBitstream($bitstreamDao, $assetstoreDao);
      $this->ItemRevision->addBitstream($itemRevisionDao, $bitstreamDao);

      if(file_exists($pathFile))
        {
        unlink($pathFile);
        }*/
      }
    }

}//end class
