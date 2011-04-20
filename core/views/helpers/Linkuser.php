<?php
class  Zend_View_Helper_Linkuser
{
  /** translation helper */
    function linkuser($userDao)
    {
    if($userDao->getPrivacy()==MIDAS_USER_PUBLIC||isset($this->view->userDao)&&$this->view->userDao->isAdmin()||isset($this->view->userDao)&&$userDao->getKey()==$this->view->userDao->getKey())
      {
      return "<a href='{$this->view->webroot}/user/{$userDao->getKey()}'>{$userDao->getFullName()}</a>";
      }
    return "{$userDao->getFullName()}";
    }//en method t



    /** Set view*/
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }
}// end class