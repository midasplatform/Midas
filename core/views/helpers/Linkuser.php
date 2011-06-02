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

class  Zend_View_Helper_Linkuser
{
  /** linkuser helper */
  function linkuser($userDao)
    {
    if($userDao->getPrivacy()==MIDAS_USER_PUBLIC||isset($this->view->userDao)&&$this->view->userDao->isAdmin()||isset($this->view->userDao)&&$userDao->getKey()==$this->view->userDao->getKey())
      {
      return "<a class=\"userTitle\" href='{$this->view->webroot}/user/{$userDao->getKey()}'>{$userDao->getFullName()}</a>";
      }
    return "{$userDao->getFullName()}";
    }//en method link user

  /** Set view*/
  public function setView(Zend_View_Interface $view)
    {
    $this->view = $view;
    }
}// end class