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

/** Share Controller */
class ShareController extends AppController
  {
  public $_models = array('Item', 'Folder', 'Group', 'Folderpolicygroup', 'Folderpolicyuser', 'Itempolicygroup', 'Itempolicyuser', 'User', 'Community');
  public $_daos = array();
  public $_components = array('Policy');
  public $_forms = array();

  /** Init Controller */
  function init()
    {

    } // end init()


  /** ajax dialog for managing permissions */
  function dialogAction()
    {
    $this->disableLayout();
    $type = $this->_getParam('type');
    $element = $this->_getParam('element');
    if(!isset($type) || !isset($element))
      {
      throw new Zend_Exception("Parameters problem.");
      }

    switch($type)
      {
      case 'folder':
        $element = $this->Folder->load($element);
        break;
      case 'item':
        $element = $this->Item->load($element);
        break;
      default:
        throw new Zend_Exception("Unknown type.");
        break;
      }

    if($element == false)
      {
      throw new Zend_Exception("Unable to load element.");
      }

    if($type == 'folder')
      {
      $isAdmin = $this->Folder->policyCheck($element, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
      }
    else
      {
      $isAdmin = $this->Item->policyCheck($element, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
      }
    if(!$isAdmin)
      {
      throw new Zend_Exception('Admin privileges required to change permissions');
      }

    if($this->_request->isPost())
      {
      $this->_helper->viewRenderer->setNoRender();
      $setPublic = $this->_getParam('setPublic');
      $setPrivate = $this->_getParam('setPrivate');
      $createPolicy = $this->_getParam('createPolicy');
      $removePolicy = $this->_getParam('removePolicy');
      $changePolicy = $this->_getParam('changePolicy');
      if(isset($changePolicy))
        {
        $changeVal = $this->_getParam('changeVal');
        $changeType = $this->_getParam('changeType');
        $changeId = $this->_getParam('changeId');
        if($changeType == 'group')
          {
          $changePolicy = $this->Group->load($changeId);
          }
        else
          {
          $changePolicy = $this->User->load($changeId);
          }

        if($type == 'folder')
          {
          if($changeType == 'group')
            {
            $policyDao = $this->Folderpolicygroup->getPolicy($changePolicy, $element);
            $this->Folderpolicygroup->delete($policyDao);
            $policyDao->setPolicy($changeVal);
            $this->Folderpolicygroup->save($policyDao);
            }
          else
            {
            $policyDao = $this->Folderpolicyuser->getPolicy($changePolicy, $element);
            $this->Folderpolicyuser->delete($policyDao);
            $policyDao->setPolicy($changeVal);
            $this->Folderpolicyuser->save($policyDao);
            }
          }
        else
          {
          if($changeType == 'group')
            {
            $policyDao = $this->Itempolicygroup->getPolicy($changePolicy, $element);
            $this->Itempolicygroup->delete($policyDao);
            $policyDao->setPolicy($changeVal);
            $this->Itempolicygroup->save($policyDao);
            }
          else
            {
            $policyDao = $this->Itempolicyuser->getPolicy($changePolicy, $element);
            $this->Itempolicyuser->delete($policyDao);
            $policyDao->setPolicy($changeVal);
            $this->Itempolicyuser->save($policyDao);
            }
          }
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      if(isset($removePolicy))
        {
        $removeType = $this->_getParam('removeType');
        $removeId = $this->_getParam('removeId');
        if($removeType == 'group')
          {
          $removePolicy = $this->Group->load($removeId);
          }
        else
          {
          $removePolicy = $this->User->load($removeId);
          }

        if($type == 'folder')
          {
          if($removeType == 'group')
            {
            $policyDao = $this->Folderpolicygroup->getPolicy($removePolicy, $element);
            $this->Folderpolicygroup->delete($policyDao);
            }
          else
            {
            $policyDao = $this->Folderpolicyuser->getPolicy($removePolicy, $element);
            $this->Folderpolicyuser->delete($policyDao);
            }
          }
        else
          {
          if($removeType == 'group')
            {
            $policyDao = $this->Itempolicygroup->getPolicy($removePolicy, $element);
            $this->Itempolicygroup->delete($policyDao);
            }
          else
            {
            $policyDao = $this->Itempolicyuser->getPolicy($removePolicy, $element);
            $this->Itempolicyuser->delete($policyDao);
            }
          }
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      if(isset($createPolicy))
        {
        $newPolicyType = $this->_getParam('newPolicyType');
        $newPolicyId = $this->_getParam('newPolicyId');
        if($newPolicyType == 'community')
          {
          $newPolicy = $this->Community->load($newPolicyId)->getMemberGroup();
          }
        elseif($newPolicyType == 'group')
          {
          $newPolicy = $this->Group->load($newPolicyId);
          }
        else
          {
          $newPolicy = $this->User->load($newPolicyId);
          }

        if($type == 'folder')
          {
          if($newPolicy instanceof GroupDao)
            {
            $this->Folderpolicygroup->createPolicy($newPolicy, $element, MIDAS_POLICY_READ);
            }
          elseif($newPolicy instanceof UserDao)
            {
            $this->Folderpolicyuser->createPolicy($newPolicy, $element, MIDAS_POLICY_READ);
            }
          else
            {
            echo JsonComponent::encode(array(false, $this->t('Error')));
            return;
            }
          }
        else
          {
          if($newPolicy instanceof GroupDao)
            {
            $this->Itempolicygroup->createPolicy($newPolicy, $element, MIDAS_POLICY_READ);
            }
          elseif($newPolicy instanceof UserDao)
            {
            $this->Itempolicyuser->createPolicy($newPolicy, $element, MIDAS_POLICY_READ);
            }
          else
            {
            echo JsonComponent::encode(array(false, $this->t('Error')));
            return;
            }
          }
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      if(isset($setPublic))
        {
        $anonymousGroup = $this->Group->load(MIDAS_GROUP_ANONYMOUS_KEY);
        if($type == 'folder')
          {
          $this->Folderpolicygroup->createPolicy($anonymousGroup, $element, MIDAS_POLICY_READ);
          }
        else
          {
          $this->Itempolicygroup->createPolicy($anonymousGroup, $element, MIDAS_POLICY_READ);
          }
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      if(isset($setPrivate))
        {
        $anonymousGroup = $this->Group->load(MIDAS_GROUP_ANONYMOUS_KEY);
        if($type == 'folder')
          {
          $policyDao = $this->Folderpolicygroup->getPolicy($anonymousGroup, $element);
          $this->Folderpolicygroup->delete($policyDao);
          }
        else
          {
          $policyDao = $this->Itempolicygroup->getPolicy($anonymousGroup, $element);
          $this->Itempolicygroup->delete($policyDao);
          }
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      }

    if($type == 'folder')
      {
      $groupPolicies = $element->getFolderpolicygroup();
      $userPolicies = $element->getFolderpolicyuser();
      }
    else
      {
      $groupPolicies = $element->getItempolicygroup();
      $userPolicies = $element->getItempolicyuser();
      }

    $private = true;
    foreach($groupPolicies as $key => $policy)
      {
      $group = $policy->getGroup();
      $groupPolicies[$key]->group = $group;
      $groupPolicies[$key]->communityMemberGroup = false;
      if($group->getKey() == MIDAS_GROUP_ANONYMOUS_KEY)
        {
        $private = false;
        unset($groupPolicies[$key]);
        continue;
        }
      if(strpos($group->getName(), 'Admin group of community') != false || strpos($group->getName(), 'Moderators group of community') != false)
        {
        unset($groupPolicies[$key]);
        continue;
        }
      if(strpos($group->getName(), 'Members group of community') !== false)
        {
        $groupPolicies[$key]->communityMemberGroup = true;
        continue;
        }
      }

    foreach($userPolicies as $key => $policy)
      {
      $userPolicies[$key]->user = $policy->getUser();
      }

    $request = Zend_Controller_Front::getInstance()->getRequest();
    $this->view->shareUrl = $request->getScheme() . '://' . $request->getHttpHost() . $this->view->webroot."/".$type."/".$element->getKey();

    $this->view->groupPolicies = $groupPolicies;
    $this->view->userPolicies = $userPolicies;
    $this->view->private = $private;
    $this->view->type = $type;
    $this->view->element = $element;

    $this->view->jsonShare = array();
    $this->view->jsonShare['type'] = $type;
    $this->view->jsonShare['element'] = $element->getKey();
    $this->view->jsonShare = JsonComponent::encode($this->view->jsonShare);
    } //end dialogAction

  /** controller for applying policies recursively to a folder */
  function applyrecursivedialogAction()
    {
    set_time_limit(0); //may take a while.
    $this->disableLayout();
    $folderId = $this->_getParam('folderId');
    $this->view->folderId = $folderId;

    if($this->_request->isPost())
      {
      $this->_helper->viewRenderer->setNoRender();
      $folder = $this->Folder->load($folderId);
      if(!$folder)
        {
        echo JsonComponent::encode(array(false, $this->t('Invalid folder id')));
        return;
        }
      $results = $this->Component->Policy->applyPoliciesRecursive($folder, $this->userSession->Dao);
      echo JsonComponent::encode(array(true, $results));
      return;
      }
    }

  }//end class