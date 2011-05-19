<?php

class Api_RestController extends Api_AppController
{

  /** Before filter */
  function preDispatch()
    {
    $this->_forward('rest', 'index', 'api', $this->_getAllParams());
    parent::preDispatch();   
    }
  } // end class
?>
