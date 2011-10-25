<?php

class Remoteprocessing_ExecutableController extends Remoteprocessing_AppController
{
  public $_models = array('Item', 'Bitstream', 'ItemRevision', 'Assetstore');
  public $_components = array('Upload');

  /** define an executable */
  function defineAction()
    {
    $this->view->header = $this->t("Item");
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

    $revision = $this->Item->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    $metaFile = false;
    foreach($bitstreams as $b)
      {
      if($b->getName() == 'MetaIO.vxml')
        {
        $metaFile = $b;
        break;
        }
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
      $xml = new SimpleXMLElement('<options></options>');
      $i = 0;
      foreach($results as $r)
        {
        $element = explode(';', $r);
        $option = $xml->addChild('option');
        $option->addChild('number',htmlspecialchars(utf8_encode($i)));
        $option->addChild('name',htmlspecialchars(utf8_encode($element[0])));
        $option->addChild('tag',htmlspecialchars(utf8_encode($element[5])));
        $option->addChild('longtag',htmlspecialchars(utf8_encode('')));
        $option->addChild('description',htmlspecialchars(utf8_encode('')));
        if($element[4] == 'True')
          {
          $option->addChild('required',htmlspecialchars(utf8_encode(1)));
          }
        else
          {
          $option->addChild('required',htmlspecialchars(utf8_encode(0)));
          }

        if($element[1] == 'ouputFile')
          {
          $option->addChild('channel',htmlspecialchars(utf8_encode('ouput')));
          }
        else
          {
          $option->addChild('channel',htmlspecialchars(utf8_encode('input')));
          }

        $option->addChild('nvalues',htmlspecialchars(utf8_encode(1)));

        $field = $option->addChild('field');
        $field->addChild('name',htmlspecialchars(utf8_encode($element[0])));
        $field->addChild('description',htmlspecialchars(utf8_encode('')));

        if($element[1] == 'inputParam')
          {
          $field->addChild('type',htmlspecialchars(utf8_encode($element[2])));
          }
        else
          {
          $field->addChild('type',htmlspecialchars(utf8_encode('string')));
          }
        $field->addChild('value',htmlspecialchars(utf8_encode('')));
        if($element[4] == 'True')
          {
          $field->addChild('required',htmlspecialchars(utf8_encode(1)));
          }
        else
          {
          $field->addChild('required',htmlspecialchars(utf8_encode(0)));
          }
        if($element[1] == 'inputParam')
          {
          $field->addChild('external',htmlspecialchars(utf8_encode(0)));
          }
        else
          {
          $field->addChild('external',htmlspecialchars(utf8_encode(1)));
          }
        }

      $pathFile = BASE_PATH.'/tmp/misc/'.uniqid().time();
      file_put_contents($pathFile, $xml->asXML());
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
      }

    }

}//end class
