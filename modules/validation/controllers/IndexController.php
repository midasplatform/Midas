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

  public $_models = array('User', 'Item', 'Folder');
  public $_moduleModels = array('Dashboard');
  public $_daos = array('Item', 'Folder');
  public $_moduleDaos = array('Dashboard');
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
    $dashboards = $this->Validation_Dashboard->getAll();
    $this->view->nSubmissions = 0;
    foreach($dashboards as $dashboard)
      {
      $this->view->nSubmissions += count($dashboard->getResults());
      }
    $this->view->dashboards = $dashboards;
    $this->view->nDashboards = count($dashboards);
    }

  } // end class
