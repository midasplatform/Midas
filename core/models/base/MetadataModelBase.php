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
  
  abstract function getMetadata($type,$element,$qualifier);
  protected abstract function saveMetadataValue($metadataDao);
  abstract function getMetadataValueExists($metadataDao);
  
  /** Add a metadata
   * @return MetadataDao */
  function addMetadata($type,$element,$qualifier,$description)
    {
    // Gets the metadata
    $metadata = $this->getMetadata($type,$element,$qualifier);  
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
    
    if(!$this->save($metadataDao))
      {
      return false;  
      } 
    return $metadataDao;
    } // end addMetadataValue() 
    
  /** Add a metadata to an itemRevision
   * @return MetadataDao */
  function addMetadataValue($itemRevisionDao,$type,$element,$qualifier,$value)
    {
    if(!$itemRevisionDao instanceof $itemRevisionDao)
      {
      throw new Zend_Exception("Error parameters.");
      }

    // Gets the metadata
    $metadataDao = $this->getMetadata($type,$element,$qualifier); 
    
    if(!$metadataDao)
      {
      throw new Zend_Exception("Metadata ".$element.".".$qualifier." doesn't exist. 
                                You should add it before adding a value.");  
      }
    $metadataDao->setItemrevisionId($itemRevisionDao->getKey());
    $metadataDao->setValue($value);
    if($this->getMetadataValueExists($metadataDao))
      {
      throw new Zend_Exception("This metadata value already exists for that revision.");   
      }
    $this->saveMetadataValue($metadataDao);
    } // end addMetadataValue()  
    
} // end class MetadataModelBase