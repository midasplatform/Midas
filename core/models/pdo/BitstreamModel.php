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
    $row = $this->database->fetchRow($this->database->select()->where('checksum = ?', $checksum)); 
    $dao = $this->initDao(ucfirst($this->_name), $row);
    return $dao;
    } // end getByChecksum()
  }  // end class
?>
