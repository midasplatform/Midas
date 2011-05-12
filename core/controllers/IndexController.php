<?php

/**
 * IndexController
 *  Index Controller
 */
class IndexController extends AppController
  {

  public $_models = array('Item');
  public $_daos = array();
  public $_components = array();
  
  /**
   * @method init()
   *  Init Controller
   */
  function init()
    {
    } //end init

  /**
   * @method indexAction()
   *  Index Action (first action when we access the application)
   */
  function indexAction()
    {
    $this->_forward('index', "feed");
    } // end method indexAction   
    
}//end class