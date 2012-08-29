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
/** packages view controller*/
class Packages_ViewController extends Packages_AppController
{
  public $_models = array('Community');
  public $_moduleModels = array('Application', 'Project');

  /**
   * View for the Packages tab within the community view.
   * Shows a list of all applications and their links
   */
  public function projectAction()
    {
    $this->disableLayout();
    $projectId = $this->_getParam('projectId');
    if(!isset($projectId))
      {
      throw new Zend_Exception('Must specify a projectId parameter');
      }
    $this->view->project = $this->Packages_Project->load($projectId);
    $this->view->community = $this->view->project->getCommunity();
    $this->view->applications = $this->Packages_Application->getAllByProjectId($projectId);

    $this->view->isAdmin = $this->Community->policyCheck(
      $this->view->community, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
    $this->view->json['projectId'] = $projectId;
    }
}//end class
