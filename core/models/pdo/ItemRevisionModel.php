<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
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
      
      
    $deleteType = array(MIDAS_FEED_CREATE_REVISION);
    $sql = $this->database->select()
                          ->setIntegrityCheck(false)
                          ->from(array('p' => 'feed'))
                          ->where('ressource = ?', $revisiondao->getKey());
    
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
?>
