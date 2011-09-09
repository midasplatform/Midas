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
/** community agreement config controller*/
class Communityagreement_ConfigController extends Communityagreement_AppController
{
  public $_models = array('Community');
  public $_moduleModels = array('Agreement');
  public $_moduleForms = array('Config');
  
  /** index */
  function indexAction()
    {
    if(!$this->logged || !$this->userSession->Dao->getAdmin() == 1)
      {
      throw new Zend_Exception("You should be an administrator");
      }
    } 
    
  /** 
  *  @method agreementtabAction() 
  *  community agreement tab. It is shown in the community manage page when the 'community agreement' module is enabled
  */   
  function agreementtabAction()
    {
   
    if(!$this->logged)  
      {
      $this->haveToBeLogged();
      return false;
      }
    if($this->_helper->hasHelper('layout')) 
      {
      $this->_helper->layout->disableLayout();
      }
      
    $communityId = $this->_getParam("communityId");
    if(!isset($communityId) || (!is_numeric($communityId) && strlen($communityId) != 32)) // This is tricky! and for Cassandra for now
      {
      throw new Zend_Exception("Community ID should be a number");
      }
      
    $agreementDao = $this->Communityagreement_Agreement->getByCommunityId($communityId);
    
    // If community agreement does not exist, show an emtpy string to the cummunity administrator
    if($agreementDao == false)
      {
      $agreement = '';
      $agreementDao = $this->Communityagreement_Agreement->createAgreement($communityId, $agreement);
      }
    
    $formAgreement = $this->ModuleForm->Config->createCreateAgreementForm($communityId);  
    if($this->_request->isPost() && $formAgreement->isValid($this->getRequest()->getPost())) 
      {
      if($this->_helper->hasHelper('layout')) 
        {
        $this->_helper->layout->disableLayout();
        }
      $this->_helper->viewRenderer->setNoRender();
      $agreementDao->setAgreement($formAgreement->getValue('agreement'));
      if($agreementDao != false)
        {
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        }
      else
        {
        echo JsonComponent::encode(array(false, $this->t('Error')));
        }
      }
    
    // if agreement only contains white spaces, delete it from the database.
    $chopped_agreement = chop($agreementDao->getAgreement());
    if($chopped_agreement != '' ) 
      {
      $this->Communityagreement_Agreement->save($agreementDao);
      } 
    else if($this->Communityagreement_Agreement->getByCommunityId($communityId) != false)
      {
      $this->Communityagreement_Agreement->delete($agreementDao);
      }  
      
    //init form
    $agreement = $formAgreement->getElement('agreement');
    $agreement->setValue($agreementDao->getAgreement());
    $this->view->agreementForm = $this->getFormAsArray($formAgreement); 
    $this->view->agreementDao = $agreementDao;         
    }
   
  /** 
  *  @method agreementdialogAction() 
  *  community agreement dialog, show the community agreements to peaple who want to join the community
    */
  function agreementdialogAction()
    {
    if($this->_helper->hasHelper('layout')) 
      {
      $this->_helper->layout->disableLayout();
      }
      
    $communityId = $this->_getParam("communityId");
    if(!isset($communityId) || (!is_numeric($communityId) && strlen($communityId) != 32)) // This is tricky! and for Cassandra for now
      {
      throw new Zend_Exception("Community ID should be a number");
      }
    
    $agreementDao = $this->Communityagreement_Agreement->getByCommunityId($communityId);  
    if($agreementDao == false)
      {
      $agreement = '';
      $agreementDao = $this->Communityagreement_Agreement->createAgreement($communityId, $agreement);
      }
    $this->view->agreementDao = $agreementDao;          
    }
   
 
  /**
  * @method checkIfAgreementEmptyAction()
   * ajax function which checks if the community agreement has been set 
   */
  public function checkagreementAction()
    {
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception("Why are you here ? Should be ajax.");
      }
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
     
    $communityId = $this->_getParam("communityId");
    $agreementDao = $this->Communityagreement_Agreement->getByCommunityId($communityId);  
    if($agreementDao != false)
      {
      echo JsonComponent::encode(MIDAS_COMMUNITYAGREEMENT_AGREEMENT_NOT_EMPTY);
      }
    else
      {
      echo JsonComponent::encode(MIDAS_COMMUNITYAGREEMENT_AGREEMENT_IS_EMPTY);
      }  
    }
 
}//end class