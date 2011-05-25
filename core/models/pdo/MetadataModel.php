<?php
require_once BASE_PATH.'/core/models/base/MetadataModelBase.php';

/**
 * \class MetadataModel
 * \brief Pdo Model
 */
class MetadataModel extends MetadataModelBase
{
  /** Return an item by its name
   * @return MetadataDao*/
  function getMetadata($type,$element,$qualifier)
    {
    $row = $this->database->fetchRow($this->database->select()
                                          ->from('metadata')
                                          ->where('metadatatype=?', $type)
                                          ->where('element=?', $element)
                                          ->where('qualifier=?', $qualifier));
    return $this->initDao('Metadata', $row);
    } // end function getMetadata()
    
  /** Return the table name based on the type of metadata*/  
  function getTableValueName($metadatatype)
    {
    switch($metadatatype)
      {
      case MIDAS_METADATA_GLOBAL: return 'metadatavalue';
      case MIDAS_METADATA_DOCUMENT: return 'metadatadocumentvalue';
      case MIDAS_METADATA_VIDEO: return 'metadatavideovalue';
      case MIDAS_METADATA_IMAGE: return 'metadataimagevalue';
      }  
    return 'metadatavalue';  
    }
    
  /** Get if a metadata value already exists */  
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
                                          ->where('itemrevision_id=?', $metadataDao->getItemrevisionId())
                                          ->where('value=?', $metadataDao->getValue()));
    
    if(count($row)>0)
      {
      return true;  
      }
    return false;                             
    }  // end getMetadataValueExists()
    
  /** Save a metadata value */
  function saveMetadataValue($metadataDao)
    {
    if(!$metadataDao instanceof MetadataDao)
      {
      throw new Zend_Exception("Should be a metadata.");
      }

	  $data['metadata_id'] = $metadataDao->getKey();
    $data['itemrevision_id'] = $metadataDao->getItemrevisionId();
    $data['value'] = $metadataDao->getValue();
    $tablename = $this->getTableValueName($metadataDao->getMetadatatype());
    $table = new Zend_Db_Table(array('name'=>$tablename,'primary'=>'metadata_id'));
    $table->insert($data);
    return true;
    } // end function saveMetadataValue()
  
} // end class
