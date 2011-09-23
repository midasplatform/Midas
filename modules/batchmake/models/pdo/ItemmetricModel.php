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
require_once BASE_PATH . '/modules/batchmake/models/base/ItemmetricModelBase.php';

/** Batchmake_ItemmetricModel */
class Batchmake_ItemmetricModel extends Batchmake_ItemmetricModelBase {

  /**
   * @return all rows stored.
   */
  public function getAll()
    {
    $rowsetDAOs = $this->database->getAll('Itemmetric', 'batchmake');
    return $rowsetDAOs;
    }


}

?>
