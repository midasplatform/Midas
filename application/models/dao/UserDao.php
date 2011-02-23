<?php
/**
 * \class ItemDao
 * \brief DAO Item (table item)
 */
class UserDao extends AppDao
  {
    public $_model='User';



  /** get full name*/
  public function getFullName()
    {
    return $this->getFirstname()." ".$this->getLastname();
    }//end class
  }
?>
