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

require_once BASE_PATH . '/modules/dicomserver/models/base/RegistrationModelBase.php';

/** Dicomserver_RegistrationModel */
class Dicomserver_RegistrationModel extends Dicomserver_RegistrationModelBase
  {
  /**
   * Returns registration by a itemId
   * @param type $itemId
   * @return type
   */
  function checkByItemId($itemId)
    {
    $row = $this->database->fetchRow($this->database->select()->where('item_id=?', $itemId));
    return $this->initDao('Registration', $row, 'dicomserver');
    }
  }
