<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

/**
 * \class ItemDao
 * \brief DAO Item (table item)
 */
class UserDao extends AppDao
  {
  public $_model = 'User';

  /**  is admin?*/
  public function isAdmin()
    {
    if($this->getAdmin() == 1)
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
    
  /** toArray */
  public function toArray()
    {
    $return = parent::toArray();
    unset($return['email']);
    unset($return['password']);
    return $return;
    }
  }
?>
