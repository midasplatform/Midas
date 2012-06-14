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

/** Metadata Model Base */
abstract class MetadataModelBase extends AppModel
{
  /** Constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'metadata';
    $this->_key = 'metadata_id';
    $this->_mainData = array(
      'metadata_id' => array('type' => MIDAS_DATA),
      'metadatatype' => array('type' => MIDAS_DATA),
      'element' => array('type' => MIDAS_DATA),
      'qualifier' => array('type' => MIDAS_DATA),
      'description' => array('type' => MIDAS_DATA),
      'value' => array('type' => MIDAS_DATA),
      'itemrevision_id' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'ItemRevision', 'parent_column' => 'itemrevision_id', 'child_column' => 'itemrevision_id'),
      );
    $this->initialize(); // required
    } // end __construct()

  abstract function getMetadata($type, $element, $qualifier);
  abstract function getAllMetadata();
  protected abstract function saveMetadataValue($metadataDao);
  abstract function getMetadataValueExists($metadataDao);

  /** Add a metadata
   * @return MetadataDao */
  function addMetadata($type, $element, $qualifier, $description)
    {
    // Gets the metadata
    $metadata = $this->getMetadata($type, $element, $qualifier);
    if($metadata)
      {
      throw new Zend_Exception("Metadata already exists.");
      }

    $this->loadDaoClass('MetadataDao');
    $metadataDao = new MetadataDao();
    $metadataDao->setMetadatatype($type);
    $metadataDao->setElement($element);
    $metadataDao->setQualifier($qualifier);
    $metadataDao->setDescription($description);

    $this->save($metadataDao);
    return $metadataDao;
    } // end addMetadataValue()

  /**
   * Add a metadata to an itemRevision, updating the value if the row
   * already exists
   * @return MetadataDao */
  function addMetadataValue($itemRevisionDao, $type, $element, $qualifier, $value, $passItemMetadataChanged = true)
    {

    if(!$itemRevisionDao instanceof $itemRevisionDao)
      {
      throw new Zend_Exception("Error parameters.");
      }

    // Gets the metadata
    $metadataDao = $this->getMetadata($type, $element, $qualifier);

    if(!$metadataDao)
      {
      throw new Zend_Exception("Metadata ".$element.".".$qualifier." doesn't exist.
                                You should add it before adding a value.");
      }
    $metadataDao->setItemrevisionId($itemRevisionDao->getKey());
    $metadataDao->setValue($value);

    $item = $itemRevisionDao->getItem();
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $lastrevision = $itemModel->getLastRevision($item);

    //refresh zend search index if latest revision has changed
    if($lastrevision->getKey() == $itemRevisionDao->getKey())
      {
      $itemModel->save($item, $passItemMetadataChanged);
      }

    $this->saveMetadataValue($metadataDao);
    return $metadataDao;
    } // end addMetadataValue()

  /**
   * Pass in one of the MIDAS_METADATA_* constants (see core/constants/metadata.php).
   * Returns the typename (ex: 'int', 'text') expected as the prefix in the Solr schema
   */
  function mapTypeToName($typeVal)
    {
    switch($typeVal)
      {
      case MIDAS_METADATA_TEXT:
        return 'text';
      case MIDAS_METADATA_INT:
        return 'int';
      case MIDAS_METADATA_DOUBLE:
        return 'double';
      case MIDAS_METADATA_FLOAT:
        return 'float';
      case MIDAS_METADATA_BOOLEAN:
        return 'bool';
      case MIDAS_METADATA_LONG:
        return 'long';
      case MIDAS_METADATA_STRING:
        return 'string';
      default:
        throw new Zend_Exception('Invalid metadata type constant passed');
      }
    }
} // end class MetadataModelBase