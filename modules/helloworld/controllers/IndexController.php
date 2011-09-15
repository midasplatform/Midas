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
class Helloworld_IndexController extends Helloworld_AppController
{

  public $_models = array('User');
  public $_moduleModels = array('Hello');
  public $_daos = array('Item');
  public $_moduleDaos = array('Hello');
  public $_components = array('Utility');
  public $_moduleComponents = array('Hello');
  public $_forms = array('Install');
  public $_moduleForms = array('Index');

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
    $configs = Zend_Registry::get('configsModules');

    //test Core Dao;
    $item = new ItemDao();

    //test Module Dao;
    $hello = new Helloworld_HelloDao();

    $this->view->version = $configs[$this->moduleName]->version;
    $this->view->componentTest = $this->ModuleComponent->Hello->hello();
    $users = $this->User->getAll();
    $this->view->users = $users;
    $this->view->imageMagick = $this->Component->Utility->isImageMagickWorking();
    $this->view->helloModel = $this->Helloworld_Hello->getAll();

    $this->view->installForm = $this->Form->Install->createConfigForm();
    $this->view->indexForm = $this->ModuleForm->Index->createIndexForm();
    }

}//end class