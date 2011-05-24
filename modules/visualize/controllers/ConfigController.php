<?php

class Visualize_ConfigController extends Visualize_AppController
{
   public $_moduleForms=array('Config');
   public $_components=array('Utility', 'Date');
   public $_moduleModels=array();

   
   /** index action*/
   function indexAction()
    {
    if(!$this->logged||!$this->userSession->Dao->getAdmin()==1)
      {
      throw new Zend_Exception("You should be an administrator");
      }
      
    if(file_exists(BASE_PATH."/core/configs/api.local.ini"))
      {
      $applicationConfig = parse_ini_file(BASE_PATH."/core/configs/api.local.ini", true);
      }
    else
      {
      $applicationConfig = parse_ini_file(BASE_PATH.'/modules/api/configs/module.ini', true);
      }
    $configForm = $this->ModuleForm->Config->createConfigForm();
    
    $formArray = $this->getFormAsArray($configForm);    
    $formArray['methodprefix']->setValue($applicationConfig['global']['methodprefix']);
    
    $this->view->configForm = $formArray;
    
    if($this->_request->isPost())
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      $submitConfig = $this->_getParam('submitConfig');
      if(isset($submitConfig))
        {
        if(file_exists(BASE_PATH."/core/configs/api.local.ini.old"))
          {
          unlink(BASE_PATH."/core/configs/api.local.ini.old");
          }
        if(file_exists(BASE_PATH."/core/configs/api.local.ini"))
          {
          rename(BASE_PATH."/core/configs/api.local.ini",BASE_PATH."/core/configs/api.local.ini.old");
          }
        $applicationConfig['global']['methodprefix'] = $this->_getParam('methodprefix');
        $this->Component->Utility->createInitFile(BASE_PATH."/core/configs/api.local.ini", $applicationConfig);
        echo JsonComponent::encode(array(true, 'Changed saved'));
        }
      }
    } 
    
}//end class