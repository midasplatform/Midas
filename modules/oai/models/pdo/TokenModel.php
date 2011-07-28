<?php
//App::import("Vendor",'Sanitize');
require_once BASE_PATH.'/modules/api/models/base/TokenModelBase.php';

class Api_TokenModel extends Api_TokenModelBase
{
 
  function cleanExpired()
    {
    $sql =$this->database->select()->where('expiration_date < ?', date("c")); 
    $rowset = $this->database->fetchAll($sql);
    foreach ($rowset as $row)
      {
      $tmpDao= $this->initDao('Token', $row,'api');
      parent::delete($tmpDao);
      }
    }
}
?>
