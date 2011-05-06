<?php
require_once BASE_PATH.'/modules/helloworld/models/base/HelloModelBase.php';

class Helloworld_HelloModel extends Helloworld_HelloModelBase
{
  /**
   * Return all the record in the table
   * @return Array of HelloDao
   */
  function getAll()
    {
    $sql=$this->database->select();
    $rowset = $this->database->fetchAll($sql);
    $rowsetAnalysed=array();
    foreach ($rowset as $keyRow=>$row)
      {
      $tmpDao= $this->initDao('Hello', $row,'helloworld');
      $rowsetAnalysed[] = $tmpDao;
      }
    return $rowsetAnalysed;
    }
    
}  // end class
?>   