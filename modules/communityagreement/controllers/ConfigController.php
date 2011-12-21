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
/**
 * Communityagreement_ConfigController
 *
 * @category   Midas modules
 * @package    communityagreement
 */
class Communityagreement_ConfigController extends Communityagreement_AppController
{
  public $_models = array('Community');
  public $_moduleModels = array('Agreement');
  public $_moduleForms = array('Config');

  /**
   * @method indexAction()
   * @throws Zend_Exception on invalid userSession
   */
  function indexAction()
    {
    $this->requireAdminPrivileges();
    }

  /** community agreement tab
   *
   * Shown in the community manage page when the 'community agreement' module is enabled
   *
   * @method agreementtabAction()
   * @throws Zend_Exception on invalid communityId
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

    // If cannot find any community agreement using the given communityID,
    // initilize the community agreement using an empty string and then create an agreementDao
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

    // If a community agreement only contains white spaces, it is treated as an empty agreement
    // and will be deleted from the database if it exists
    $chopped_agreement = chop($agreementDao->getAgreement());
    if($chopped_agreement != '' )
      {
      $this->Communityagreement_Agreement->save($agreementDao);
      }
    else if($this->Communityagreement_Agreement->getByCommunityId($communityId) != false)
      {
      $this->Communityagreement_Agreement->delete($agreementDao);
      }

    // init form
    $agreement = $formAgreement->getElement('agreement');
    $agreement->setValue($agreementDao->getAgreement());
    $this->view->agreementForm = $this->getFormAsArray($formAgreement);
    $this->view->agreementDao = $agreementDao;
    }

  /**
   * community agreement dialog
   *
   * When a user wants to read the community agreement before joining the community, the "agreement" link will be clicked
   * and this dialog will be shown
   *
   * @method agreementdialogAction()
   * @throws Zend_Exception on invalid communityId
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
   * ajax function which checks if the community agreement has been set
   *
   * @method checkagreementAction()
   * @throws Zend_Exception on invalid request
  */
  public function checkagreementAction()
    {
    $this->requireAjaxRequest();
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