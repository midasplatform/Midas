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
/** Executable controller */
class Remoteprocessing_ExecutableController extends Remoteprocessing_AppController
{
  public $_models = array('Item', 'Bitstream', 'ItemRevision', 'Assetstore');
  public $_components = array('Upload');
  public $_moduleComponents = array('Executable');

  /** define an executable */
  function defineAction()
    {
    $itemId = $this->_getParam("itemId");
    if(!isset($itemId) || !is_numeric($itemId))
      {
      throw new Zend_Exception("itemId  should be a number");
      }

    $isAjax = $this->getRequest()->isXmlHttpRequest();
    $this->view->isAjax = $isAjax;
    if($isAjax)
      {
      $this->disableLayout();
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
    $this->view->header = $this->t("Manage Configuration: ".$itemDao->getName());
    $metaFile = $this->ModuleComponent->Executable->getMetaIoFile($itemDao);

    if(isset($_GET['init']))
      {
      $this->showNotificationMessage('Please set the option of the executable first.');
      }

    $jsonContents = JsonComponent::encode(array());
    if($metaFile !== false)
      {
      $jsonContents = Zend_Json::fromXml(file_get_contents($metaFile->getFullPath()), true);
      }
    $this->view->itemDao = $itemDao;
    $this->view->jsonMetadata = $jsonContents;
    $this->view->json['item'] = $itemDao->toArray();
    if($this->_request->isPost())
      {
      $this->disableLayout();
      $this->disableView();

      $results = $_POST['results'];
      $xmlContent = $this->ModuleComponent->Executable->createDefinitionFile($results);
      $pathFile = $this->getTempDirectory().'/'.uniqid().time();
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
      $assetstoreDao = $this->Assetstore->getDefault();
      $bitstreamDao->setAssetstoreId($assetstoreDao->getKey());

      // Upload the bitstream if necessary (based on the assetstore type)
      $this->Component->Upload->uploadBitstream($bitstreamDao, $assetstoreDao);
      $this->ItemRevision->addBitstream($itemRevisionDao, $bitstreamDao);

      if(file_exists($pathFile))
        {
        unlink($pathFile);
        }
      }

    }

}//end class
