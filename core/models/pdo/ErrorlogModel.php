<?php
require_once BASE_PATH.'/core/models/base/ErrorlogModelBase.php';

/**
 * \class ErrorlogModel
 * \brief Pdo Model
 */
class ErrorlogModel extends ErrorlogModelBase
{ 
    
  /**
   *  Return a list of log
   * @param type $startDate
   * @param type $endDate
   * @param type $module
   * @param type $priority
   * @param type $limit
   * @return array ErrorlogDao
   */
  function getLog($startDate, $endDate, $module = 'all', $priority = 'all', $limit = 99999)
    {
    $result = array();
    $sql = $this->database->select()
            ->setIntegrityCheck(false)
            ->from(array('e' => 'errorlog'))
            ->where('datetime >= ?', $startDate)
            ->where('datetime <= ?', $endDate)
            ->order('datetime DESC')
            ->limit($limit);
    if($module != 'all')
      {
      $sql->where('module = ?', $module);
      }
    if($priority != 'all')
      {
      $sql->where('priority = ?', $priority);
      }
    $rowset = $this->database->fetchAll($sql);
    foreach($rowset as $keyRow => $row)
      {
      $result[] = $this->initDao('Errorlog', $row);
      }
    return $result;
    }//getLog
    
} // end class
?>
