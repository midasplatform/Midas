<?php

/** Api controller for /json */
class Api_JsonController extends Api_AppController
{

  /** Before filter */
  function preDispatch()
    {
    $this->_forward('json', 'index', 'api', $this->_getAllParams());
    parent::preDispatch();
    }
  } // end class
?>
