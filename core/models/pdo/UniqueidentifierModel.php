<?php
require_once BASE_PATH.'/core/models/base/UniqueidentifierModelBase.php';

/**
 * \class UniqueidentifierModel
 * \brief Pdo Model
 */
class UniqueidentifierModel extends UniqueidentifierModelBase
{
  /** Get identifier*/
  public function getIndentifier($dao)
    {
    $type = $this->_getType($dao);
    return $this->initDao('Uniqueidentifier', $this->database->fetchRow($this->database->select()->where('resource_type = ?', $type)->where('resource_id = ?', $dao->getKey())));
    }
    
  /** Get using id*/
  public function getByUid($uid)
    {
    return $this->initDao('Uniqueidentifier', $this->database->fetchRow($this->database->select()->where('uniqueidentifier_id = ?', $uid)));
    }
}  // end class
?>
