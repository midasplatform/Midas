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
  public $_moduleModels = array('Application', 'Package', 'Project');

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

  /**
   * View the release packages for an application
   */
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
    $this->view->isAdmin = $this->Community->policyCheck($comm, $this->userSession->Dao, MIDAS_POLICY_ADMIN);

    $this->view->header = '<ul class="pathBrowser"><li>'.
                          '<img alt="" src="'.$this->view->moduleWebroot.'/public/images/package.png" />'.
                          '<span><a href="'.$this->view->webroot.'/community/'.$comm->getKey().'#Packages">'.$comm->getName().
                          ' Packages</a></span></li>'.
                          '<li><img alt="" src="'.$this->view->moduleWebroot.'/public/images/application_terminal.png" />'.
                          '<span><a href="#">'.$application->getName().'</a></span></li></ul>';

    $this->view->application = $application;
    $this->view->json['applicationId'] = $application->getKey();

    $this->view->releases = $this->Packages_Application->getAllReleases($application);
    usort($this->view->releases, array($this, '_releaseSort'));

    if(count($this->view->releases) > 0)
      {
      $this->view->json['openRelease'] = $this->view->releases[0];
      $this->view->json['latestReleasePackages'] = $this->Packages_Package->get(array(
        'application_id' => $application->getKey(),
        'release' => $this->view->releases[0]));
      }
    }

  /**
   * View for latest builds
   */
  public function latestAction()
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
                          '<span><a href="'.$this->view->webroot.'/community/'.$comm->getKey().'#Packages">'.$comm->getName().
                          ' Packages</a></span></li>'.
                          '<li><img alt="" src="'.$this->view->moduleWebroot.'/public/images/application_terminal.png" />'.
                          '<span><a href="'.$this->view->webroot.'/packages/application/view?applicationId='.$application->getKey().
                          '">'.$application->getName().'</a></span></li>'.
                          '<li><img alt="" src="'.$this->view->coreWebroot.'/public/images/icons/time.png" /><span>'.
                          '<a href="#">Latest Nightly Packages</a></span></li></ul>';

    $this->view->platforms = $this->Packages_Application->getDistinctPlatforms($application);
    }

  /**
   * Helper function for sorting releases (desc)
   */
  private function _releaseSort($a, $b)
    {
    $a_tok = explode('.', $a);
    $b_tok = explode('.', $b);

    $a_count = count($a_tok);
    $b_count = count($b_tok);
    for($i = 0; $i < $a_count && $i < $b_count; $i++)
      {
      $a_v = (int)$a_tok[$i];
      $b_v = (int)$b_tok[$i];
      if($a_v > $b_v)
        {
        return -1;
        }
      else if($a_v < $b_v)
        {
        return 1;
        }
      }

    if($a_count == $b_count)
      {
      return 0;
      }
    if($a_count < $b_count)
      {
      return 1;
      }
    return -1;
    }

  /**
   * Ajax action to return a list of packages for a given application and release
   */
  public function getpackagesAction()
    {
    $this->disableLayout();
    $this->disableView();
    $releasePackages = $this->Packages_Package->get(array(
        'application_id' => $this->_getParam('applicationId'),
        'release' => $this->_getParam('release')));
    echo JsonComponent::encode($releasePackages);
    }
}//end class
