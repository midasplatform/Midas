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

require_once BASE_PATH.'/core/models/base/AssetstoreModelBase.php';

/**
 * \class AssetstoreModel
 * \brief Pdo Model
 */
class AssetstoreModel extends AssetstoreModelBase
{
  /** get All */
  function getAll()
    {
    return $this->database->getAll('Assetstore');
    }
    
}  // end class
