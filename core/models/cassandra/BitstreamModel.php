<?php
require_once BASE_PATH.'/core/models/base/BitstreamModelBase.php';

/**
 * \class BitstreamModel
 * \brief Pdo Model
 */
class BitstreamModel extends BitstreamModelBase
{
  /** Get bitstream by checksum */
  function getByChecksum($checksum)
    {
    $bitstreamchecksum = $this->database->getCassandra('bitstreamchecksum',$checksum);
    
    if(empty($bitstreamchecksum))
      {
      return null;
      }
    $bitstreamid = $bitstreamchecksum['bitstream_id'];
    
    $bitstream = $this->database->getCassandra('bitstream',$bitstreamid);
    $bitstream['bitstream_id'] = $bitstreamid;
    $dao= $this->initDao('Bitstream',$bitstream);      
    return $dao;
     
    } // end getByChecksum()
  
}
?>
