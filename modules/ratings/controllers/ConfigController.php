<?php

/** Configure controller for ratings module */
class Ratings_ConfigController extends Ratings_AppController
  {
  /** index action*/
  function indexAction()
    {
    $this->requireAdminPrivileges();
    }

  } // end class
