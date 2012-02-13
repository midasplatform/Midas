<?php

/** Configure controller for comments module */
class Comments_ConfigController extends Comments_AppController
{
  /** index action*/
  function indexAction()
    {
    $this->requireAdminPrivileges();
    }

}//end class
