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
/** demo controller*/
class Validation_IndexController extends Validation_AppController
{

  public $_models = array('User');
  public $_moduleModels = array('Validation');
  public $_daos = array('Item');
  public $_moduleDaos = array('Validation');
  public $_components = array('Utility');
  public $_moduleComponents = array();
  public $_forms = array();
  public $_moduleForms = array();

  /**
   * @method initAction()
   *  Index Action (first action when we access the application)
   */
  function init()
    {

    } // end method indexAction

  /** index action*/
  function indexAction()
    {
    $this->view->validationText = $this->t('VALIDATION');
    }

}//end class
