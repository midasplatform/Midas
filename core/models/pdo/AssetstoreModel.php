<?php
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
?>
