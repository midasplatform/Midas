<?php

/**
 *  GlobalComponent
 *  Provides global function to the components
 */
class MIDAS_GlobalFilter extends Zend_Controller_Action_Helper_Abstract
  {

    /**
   * Get Logger
   * @return Zend_Log
   */
  public function getLogger()
    {
    return Zend_Registry::get('logger');
    }

  } // end class

?>