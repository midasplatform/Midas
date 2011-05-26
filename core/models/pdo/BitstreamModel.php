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
