<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
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