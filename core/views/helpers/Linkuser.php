<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
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