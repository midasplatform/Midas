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

require_once BASE_PATH.'/core/models/base/ItemRevisionModelBase.php';

/**
 * \class ItemRevisionModel
 * \brief Pdo Model
 */
class ItemRevisionModel extends ItemRevisionModelBase
{
  /** get by uuid*/
  function getByUuid($uuid)
    {
    $row = $this->database->fetchRow($this->database->select()->where('uuid = ?', $uuid));
    $dao = $this->initDao(ucfirst($this->_name), $row);
    return $dao;
    }

  /** get the metadata associated with the revision */
  function getMetadata($revisiondao)
    {
    if(!$revisiondao instanceof ItemRevisionDao)
      {
      throw new Zend_Exception("Error param.");
      }

    $metadatavalues = array();
    $sql = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from('metadatavalue')
                          ->where('itemrevision_id = ?', $revisiondao->getKey())
                          ->joinLeft('metadata', 'metadata.metadata_id = metadatavalue.metadata_id');

    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $row)
      {
      $metadata = $this->initDao('Metadata', $row);
      $metadatavalues[] = $metadata;
      }

    return $metadatavalues;
    }  // end getMetadata

  /** get the metadata associated with the revision */
  function deleteMetadata($revisiondao, $metadataId)
    {
    if(!$revisiondao instanceof ItemRevisionDao || !is_numeric($metadataId))
      {
      throw new Zend_Exception("Error param.");
      }

    Zend_Registry::get('dbAdapter')->delete('metadatavalue', 'itemrevision_id = '.$revisiondao->getKey().' AND metadata_id = '.$metadataId);

    $item = $revisiondao->getItem();
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $lastrevision = $itemModel->getLastRevision($item);

    //refresh zend search index
    if($lastrevision->getKey() == $revisiondao->getKey())
      {
      $itemModel->save($item);
      }
    return;
    }  // end getMetadata

  /** delete a revision*/
  function delete($revisiondao)
    {
    if(!$revisiondao instanceof ItemRevisionDao)
      {
      throw new Zend_Exception("Error param.");
      }
    $bitstreams = $revisiondao->getBitstreams();
    $this->ModelLoader = new MIDAS_ModelLoader();
    $bitstream_model = $this->ModelLoader->loadModel('Bitstream');
    foreach($bitstreams as $bitstream)
      {
      $bitstream_model->delete($bitstream);
      }

    // explicitly typecast the id to a string, for postgres
    $deleteType = array(MIDAS_FEED_CREATE_REVISION);
    $sql = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'feed'))
                          ->where('ressource = ?', (string)$revisiondao->getKey());

    $rowset = $this->database->fetchAll($sql);
    $this->ModelLoader = new MIDAS_ModelLoader();
    $feed_model = $this->ModelLoader->loadModel('Feed');
    foreach($rowset as $row)
      {
      $feed = $this->initDao('Feed', $row);
      if(in_array($feed->getType(), $deleteType))
        {
        $feed_model->delete($feed);
        }
      }

    parent::delete($revisiondao);
    $revisiondao->saved = false;
    unset($revisiondao->itemrevision_id);
    }//end delete


  /** Returns the latest revision of a model */
  function getLatestRevision($itemdao)
    {
    $row = $this->database->fetchRow($this->database->select()->from($this->_name)->where('item_id=?', $itemdao->getItemId())->order('revision DESC')->limit(1));
    return $this->initDao('ItemRevision', $row);
    }

  /** Returns the of the revision in Bytes */
  function getSize($revision)
    {
    $row = $this->database->fetchRow($this->database->select()
                            ->setIntegrityCheck(false)->from('bitstream', array('sum(sizebytes) as sum'))->where('itemrevision_id=?', $revision->getKey()));
    return $row['sum'];
    }

  /** Return a bitstream by name */
  function getBitstreamByName($revision, $name)
    {
    $row = $this->database->fetchRow($this->database->select()->setIntegrityCheck(false)
                                          ->from('bitstream')
                                          ->where('itemrevision_id=?', $revision->getItemrevisionId())
                                          ->where('name=?', $name));
    return $this->initDao('Bitstream', $row);
    } // end getBitstreamByName
} // end class
