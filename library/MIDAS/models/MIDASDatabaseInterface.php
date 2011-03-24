<?php
interface MIDASDatabaseInterface
{
  public function save($dataarray);
  public function delete($dao);
  public function getValue($var, $key, $dao);
  
} // end interface
?>