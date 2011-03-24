<?php
interface MIDASDatabaseInterface
{
  public function save($dao);
  public function delete($dao);
  public function getValue($var, $key, $dao);
  
} // end interface
?>