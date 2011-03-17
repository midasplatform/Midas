<?php

/**
 *  Assetstore Controller
 *  Assetstore Controller
 */
class AssetstoreController extends AppController
  {

  public $_models=array('Assetstore');
  public $_daos=array('Assetstore');
  public $_components=array();
  public $_forms=array('Assetstore');
  
  /**
   * @method init()
   *  Init Controller
   */
  function init()
    { 
    $this->view->menu = "assetstore";
    }  // end init() 
    
  /**
   * \fn indexAction()
   * \brief Index Action (first action when we access the application)
   */
  function indexAction()
    {      
    
    }// end indexAction
    
  /**
   * \fn indexAction()
   * \brief called from ajax
   */
  function addAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
      
    $form = $this->Form->Assetstore->createAssetstoreForm();
    if($this->getRequest()->isPost() && !$form->isValid($_POST)) 
      {
      echo json_encode(array('error' => 'The form is invalid. Missing values.'));
      return false;
      }
      
    if($this->getRequest()->isPost() && $form->isValid($_POST))
      {
      // Save the assetstore in the database
      $assetstoreDao = new AssetstoreDao();
      $assetstoreDao->setName($form->name->getValue());
      $assetstoreDao->setPath($form->basedirectory->getValue());
      $assetstoreDao->setType($form->type->getValue());
      $this->Assetstore->save($assetstoreDao);  
        
      echo json_encode(array('msg' => 'The assetstore has been added.',
                       'assetstore_id' => $assetstoreDao->getAssetstoreId(),
                       'assetstore_name' => $assetstoreDao->getName(),
                       'assetstore_type' => $assetstoreDao->getType()
                       ));  
      return true;
      }
      
    echo json_encode(array('error' => 'Bad request.'));  
    return false;
    } // end import action
    
    
} // end class

  