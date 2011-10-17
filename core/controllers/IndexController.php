<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

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
    $this->_redirect("/feed");
    } // end method indexAction

  /** no javascript*/
  function nojsAction()
    {
    $this->disableLayout();
    } // end method indexAction

  /** no valid browser*/
  function nobrowserAction()
    {
    $this->disableLayout();
    } // end method indexAction

}//end class