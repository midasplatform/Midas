<?php
/**
 * \class ItemDao
 * \brief DAO Item (table item)
 */
class UserDao extends AppDao
  {
    public $_model='User';

  /* is admin?*/
  public function isAdmin()
    {
    if($this->getAdmin()==1)
      {
      return true;
      }
    return false;
    }/// end isAmdin

  /** get full name*/
  public function getFullName()
    {
    return $this->getFirstname()." ".$this->getLastname();
    }//end class
  }
?>
