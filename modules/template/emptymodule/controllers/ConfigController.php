<?php

class Modulename_ConfigController extends Modulename_AppController
{

   function indexAction()
    {
    if(!$this->logged||!$this->userSession->Dao->getAdmin()==1)
      {
      throw new Zend_Exception("You should be an administrator");
      }
    } 
    
}//end class