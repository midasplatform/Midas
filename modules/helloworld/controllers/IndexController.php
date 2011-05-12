<?php

class Helloworld_IndexController extends Helloworld_AppController
{

  public $_models=array('User');
  public $_moduleModels=array('Hello');
  public $_daos=array('Item');
  public $_moduleDaos=array('Hello');
  public $_components=array('Utility');
  public $_moduleComponents=array('Hello');
  public $_forms=array('Install');
  public $_moduleForms=array('Index');
  
  /**
   * @method initAction()
   *  Index Action (first action when we access the application)
   */
  function init()
   {         
  
   } // end method indexAction

   function indexAction()
    {
     $configs=Zend_Registry::get('configsModules');
     
     //test Core Dao;
     $item = new ItemDao();
     
     //test Module Dao;
     $hello = new Helloworld_HelloDao();
         
     $this->view->version=$configs[$this->moduleName]->version;
     $this->view->componentTest=$this->ModuleComponent->Hello->hello();
     $this->view->users=$this->User->load(1);
     $this->view->imageMagick=$this->Component->Utility->isImageMagickWorking();
     $this->view->helloModel=$this->Helloworld_Hello->getAll();

     $this->view->installForm=$this->Form->Install->createConfigForm();
     $this->view->indexForm=$this->ModuleForm->Index->createIndexForm();
    } 
    
}//end class