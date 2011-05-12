<?php
/** This class handles the upload of files into the different assetstores */
class UploadComponent extends AppComponent
{  
  
  /** Helper function to create the two-level hierarchy */
  private function _createAssetstoreDirectory($directorypath)
    {
    if(!file_exists($directorypath))
      {
      if(!mkdir($directorypath))
        {
        throw new Zend_Exception("Cannot create directory: ".$directorypath);   
        }
      chmod($directorypath, 0777);
      }  
    } // end _createAssetstoreDirectory()
  
  /** Upload local bitstream */
  private function _uploadLocalBitstream($bitstreamdao, $assetstoredao)
    {
    // Check ifthe type of the assestore is suitable
    if($assetstoredao->getType() != MIDAS_ASSETSTORE_LOCAL)
      {
      throw new Zend_Exception("The assetstore type should be local to upload.");   
      }
    
    // Check ifthe path of the assetstore exists on the server  
    if(!is_dir($assetstoredao->getPath()))
      {
      throw new Zend_Exception("The assetstore path doesn't exist.");   
      }

    // Check ifthe MD5 exists for the bitstream
    $checksum = $bitstreamdao->getChecksum();  
    if(empty($checksum))
      {
      throw new Zend_Exception("Checksum is not set.");
      }
      
    // Two-level hierarchy.  
    $path = substr($checksum, 0, 2).'/'.substr($checksum, 2, 2).'/'.$checksum;
    $fullpath = $assetstoredao->getPath().'/'.$path;

    // This should be rare (MD5 has a low probably for collisions)
    if(file_exists($fullpath))
      {
      //throw new Zend_Exception("File already in the assetstore.");   
      return false;
      }

    //Create the directories
    $currentdir = $assetstoredao->getPath().'/'.substr($checksum, 0, 2);
    $this->_createAssetstoreDirectory($currentdir);
    $currentdir .= '/'.substr($checksum, 2, 2);
    $this->_createAssetstoreDirectory($currentdir);
        
    // Do the actual copy
    // Do not delete anything. This is the responsability of the controller
    copy($bitstreamdao->getPath(), $fullpath);

    // Set the new path
    $bitstreamdao->setPath($path);
    
    } // end _uploadLocalBitstream()
 
  /** Upload a bitstream */  
  function uploadBitstream($bitstreamdao, $assetstoredao)
    { 
    $assetstoretype = $assetstoredao->getType();
    switch($assetstoretype)
      {
      case MIDAS_ASSETSTORE_LOCAL: 
        $this->_uploadLocalBitstream($bitstreamdao, $assetstoredao); 
        break;
      case MIDAS_ASSETSTORE_REMOTE: 
        // Nothing to upload in that case, we return silently
        return true;
      case MIDAS_ASSETSTORE_AMAZON: 
        throw new Zend_Exception("Amazon support is not implemented yet."); 
        break;
      default :
        break;
      }
    return true;  
    } // end uploadBitstream() 
  

} // end class UploadComponent
?>
