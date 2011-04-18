<?php

class Helloworld_ConfigController extends Helloworld_AppController
{

   function indexAction()
    {
    if(!$this->logged||!$this->userSession->Dao->getAdmin()==1)
      {
      throw new Zend_Exception("You should be an administrator");
      }
    } 
    
}//end class