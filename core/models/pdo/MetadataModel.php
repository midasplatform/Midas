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

require_once BASE_PATH.'/core/models/base/MetadataModelBase.php';

/**
 * \class MetadataModel
 * \brief Pdo Model
 */
class MetadataModel extends MetadataModelBase
  {

  /**
   * Get all metadata types currently stored in the database.
   */
  function getMetadataTypes()
    {
    $rowset = $this->database->fetchAll($this->database->select()
                                        ->from('metadata', 'metadatatype')
                                        ->group('metadatatype'));
    $types = array();
    foreach($rowset as $row)
      {
      $types[] = $this->mapTypeToName($row['metadatatype']);
      }
    return $types;
    }

  /**
   * Get all the valid elements for a given metadata type
   */
  function getMetadataElements($type)
    {
    $rowset = $this->database->fetchAll($this->database->select()
                                        ->from('metadata', 'element')
                                        ->where('metadatatype=?', $type)
                                        ->group('element'));
    $elements = array();
    foreach($rowset as $row)
      {
      $elements[] = $row['element'];
      }
    return $elements;
    }

  /**
   * Get all of the valid qualifiers for a given type and element
   */
  function getMetadataQualifiers($type, $element)
    {
    $rowset = $this->database->fetchAll($this->database->select()
                                        ->from('metadata', 'qualifier')
                                        ->where('metadatatype=?', $type)
                                        ->where('element=?', $element)
                                        ->group('qualifier'));
    $qualifiers = array();
    foreach($rowset as $row)
      {
      $qualifiers[] = $row['qualifier'];
      }
    return $qualifiers;
    }

  /** Return an item by its name
   * @return MetadataDao*/
  function getMetadata($type, $element, $qualifier)
    {
    $row = $this->database->fetchRow($this->database->select()
                                          ->from('metadata')
                                          ->where('metadatatype=?', $type)
                                          ->where('element=?', $element)
                                          ->where('qualifier=?', $qualifier));
    return $this->initDao('Metadata', $row);
    } // end function getMetadata()

  /** get all the metadata */
  function getAllMetadata()
    {
    $rowset = $this->database->fetchAll($this->database->select());

    $metadata = array('raw' => array(), 'sorted' => array());
    foreach($rowset as $row)
      {
      $dao = $this->initDao('Metadata', $row);
      $metadata['raw'][] = $dao;
      $metadata['sorted'][$dao->getMetadatatype()][$dao->getElement()][] = $dao;
      }
    ksort($metadata['sorted']);
    foreach($metadata['sorted'] as $key => $v)
      {
      ksort($metadata['sorted'][$key]);
      }
    return $metadata;
    } // end function getMetadata()

  /** Return the table name based on the type of metadata*/
  function getTableValueName($metadatatype)
    {
    return 'metadatavalue';
    }

  /** Return true if a metadata value exists for this metadata. */
  function getMetadataValueExists($metadataDao)
    {
    if(!$metadataDao instanceof MetadataDao)
      {
      throw new Zend_Exception("Should be a metadata.");
      }

    $row = $this->database->fetchRow($this->database->select()
                                          ->setIntegrityCheck(false)
                                          ->from($this->getTableValueName($metadataDao->getMetadatatype()))
                                          ->where('metadata_id=?', $metadataDao->getKey())
                                          ->where('itemrevision_id=?', $metadataDao->getItemrevisionId()));

    if(count($row) > 0)
      {
      return true;
      }
    return false;
    }  // end getMetadataValueExists()

  /**
   * Save a metadata value, will update the row if it exists, otherwise insert
   */
  function saveMetadataValue($metadataDao)
    {
    if(!$metadataDao instanceof MetadataDao)
      {
      throw new Zend_Exception("Should be a metadata.");
      }

    $cols = array('metadata_id' => $metadataDao->getKey(),
                  'itemrevision_id' => $metadataDao->getItemrevisionId());

    $data['value'] = $metadataDao->getValue();
    $tablename = $this->getTableValueName($metadataDao->getMetadatatype());
    $table = new Zend_Db_Table(array('name' => $tablename, 'primary' => 'metadata_id'));
    if($this->getMetadataValueExists($metadataDao))
      {
      $wheres = array();
      foreach($cols as $col => $val)
        {
        $wheres[$col."=?"] = $val;
        }
      $table->update($data, $wheres);
      }
    else
      {
      foreach($cols as $col => $val)
        {
        $data[$col] = $val;
        }
      $table->insert($data);
      }
    return true;
    } // end function saveMetadataValue()
  } // end class
