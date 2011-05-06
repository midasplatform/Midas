<?php
interface MIDASDatabaseInterface
{
  /** generic save*/
  public function save($dataarray);
  /** generic delete*/
  public function delete($dao);
  /** generic get value*/
  public function getValue($var, $key, $dao);
  /** generic get all by key*/
  public function getAllByKey($keys);      
} // end interface