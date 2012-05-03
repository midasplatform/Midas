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
/** packages application controller*/
class Packages_ApplicationController extends Packages_AppController
{
  public $_models = array('Community');
  public $_moduleDaos = array('Application');
  public $_moduleModels = array('Application', 'Project');

  /**
   * Create a new application under a project
   * @param projectId Project id
   * @param name Name of the application
   * @param description Description of the application
   */
  public function createAction()
    {
    $projectId = $this->_getParam('projectId');
    if(!isset($projectId))
      {
      throw new Zend_Exception('Must specify a projectId parameter');
      }
    $name = $this->_getParam('name');
    $description = $this->_getParam('description');

    if(!isset($name))
      {
      throw new Zend_Exception('Parameter "name" must be set');
      }
    if(!isset($description))
      {
      throw new Zend_Exception('Parameter "description" must be set');
      }
    $project = $this->Packages_Project->load($projectId);
    if(!$project)
      {
      throw new Zend_Exception('Invalid projectId');
      }

    if(!$this->Community->policyCheck($project->getCommunity(), $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception('Must be community administrator to add a new application');
      }
    $this->disableLayout();
    $this->disableView();

    $application = new Packages_ApplicationDao();
    $application->setName($name);
    $application->setDescription($description);
    $application->setProjectId($projectId);
    $this->Packages_Application->save($application);
    $this->_redirect('/packages/application/view?applicationId='.$application->getKey());
    }

  public function viewAction()
    {
    $applicationId = $this->_getParam('applicationId');
    if(!isset($applicationId))
      {
      throw new Zend_Exception('Must specify an applicationId parameter');
      }
    $application = $this->Packages_Application->load($applicationId);
    if(!$application)
      {
      throw new Zend_Controller_Action_Exception('Invalid applicationId', 404);
      }
    $comm = $application->getProject()->getCommunity();
    if(!$this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception('You do not have read permissions on the project');
      }
    $this->view->header = '<ul class="pathBrowser"><li>'.
                          '<img alt="" src="'.$this->view->moduleWebroot.'/public/images/package.png" />'.
                          '<span><a href="'.$this->view->webroot.'/community/'.$comm->getKey().'#Packages">'.
                          $comm->getName().' Packages</a></span></li>'.
                          '<li><img alt="" src="'.$this->view->moduleWebroot.'/public/images/application_terminal.png" />'.
                          '<span><a href="#">'.$application->getName().'</a></span></li></ul>';
    }
}//end class
