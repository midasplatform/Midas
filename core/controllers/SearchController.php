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
 *  Search controller
 */
class SearchController extends AppController
{
  public $_models = array('Item', 'Folder', 'User', 'Community', 'Group');
  public $_daos = array('Item', 'Folder', 'User', 'Community');
  public $_components = array('Sortdao', 'Date', 'Utility', 'Search');

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = 'feed'; // set the active menu

    // ifthe number of parameters is more than 3 then it's the liveAction or advanced search
    if(count($this->_getAllParams()) == 3)
      {
      $actionName = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
      $this->_forward('index', null, null, array('q' => $actionName));
      }
    }  // end init()


  /** search live Action */
  public function indexAction()
    {
    $this->view->header = $this->t("Search");

    // Pass the keyword to javascript
    $keyword = $this->getRequest()->getParam('q');
    $this->view->json['search']['keyword'] = $keyword;

    $ajax = $this->_getParam('ajax');
    $order = $this->_getParam('order');
    if(!isset($order))
      {
      $order = 'view';
      }

    $results = $this->Component->Search->searchAll($this->userSession->Dao, $keyword, $order);
    if(isset($ajax))
      {
      $this->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      echo JsonComponent::encode($results);
      }
    else
      {
      $this->view->nitems = $results['nitems'];
      $this->view->nfolders = $results['nfolders'];
      $this->view->ncommunities = $results['ncommunities'];
      $this->view->nusers = $results['nusers'];
      $this->view->json['search']['results'] = $results['results'];
      $this->view->json['search']['keyword'] = $keyword;
      $this->view->json['search']['noResults'] = $this->t('No result found.');
      $this->view->json['search']['moreResults'] = $this->t('Show more results.');
      }
    }//end indexAction



  /** search live Action */
  public function liveAction()
    {
    // This is necessary in order to avoid session lock and being able to run two
    // ajax requests simultaneously
    session_write_close();

    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $search = $this->getRequest()->getParam('term');
    $shareSearch = $this->getRequest()->getParam('shareSearch'); //return user group and communities
    $userSearch = $this->getRequest()->getParam('userSearch');
    $itemSearch = $this->getRequest()->getParam('itemSearch');

    if(isset($shareSearch))
      {
      $ItemsDao = array();
      $FoldersDao = array();

      // Search for the communities
      $CommunitiesDao = $this->Community->getCommunitiesFromSearch($search, $this->userSession->Dao);
      $GroupsDao = array();
      foreach($CommunitiesDao as $communitieDao)
        {
        $groups = $communitieDao->getGroups();
        if(!$this->Community->policyCheck($communitieDao, $this->userSession->Dao))
          {
          continue;
          }
        foreach($groups as $key => $group)
          {
          $group->setName($communitieDao->getName().' ('.$group->getName().')');
          $group->community = $communitieDao;
          $GroupsDao[] = $group;
          }
        }

      $CommunitiesDao = array();
      // Search for the users
      $UsersDao = $this->User->getUsersFromSearch($search, $this->userSession->Dao);
      }
    elseif(isset($userSearch))
      {
      $ItemsDao = array();
      $FoldersDao = array();
      $CommunitiesDao = array();
      $GroupsDao = array();
      // Search for the users
      $UsersDao = $this->User->getUsersFromSearch($search, $this->userSession->Dao);
      }
    elseif(isset($itemSearch))
      {
      $ItemsDao = $this->Item->getItemsFromSearch($search, $this->userSession->Dao, 15, false);
      $FoldersDao = array();
      $CommunitiesDao = array();
      $GroupsDao = array();
      // Search for the users
      $UsersDao = array();
      }
    else
      {
      // Search for the items
      $ItemsDao = $this->Item->getItemsFromSearch($search, $this->userSession->Dao);

      // Search for the folders
      $FoldersDao = $this->Folder->getFoldersFromSearch($search, $this->userSession->Dao);

      // Search for the communities
      $CommunitiesDao = $this->Community->getCommunitiesFromSearch($search, $this->userSession->Dao);

      // Search for the users
      $UsersDao = $this->User->getUsersFromSearch($search, $this->userSession->Dao);
      $GroupsDao = array();
      }


    // Compute how many of each we should display
    $nitems = count($ItemsDao);
    $nfolders = count($FoldersDao);
    $ncommunities = count($CommunitiesDao);
    $ngroups = count($GroupsDao);
    $nusers = count($UsersDao);

    $nmaxfolders = ($nfolders < 3) ? $nfolders : 3;
    $nmaxcommunities = ($ncommunities < 3) ? $ncommunities : 3;
    $nmaxusers = ($nusers < 3) ? $nusers : 3;

    if($nitems > 5)
      {
      $nitems = 14 - ($nmaxfolders + $nmaxcommunities + $nmaxusers);
      }

    if($nfolders > 3)
      {
      $nfolders = 14 - ($nitems + $nmaxcommunities + $nmaxusers);
      }

    if($ncommunities > 3)
      {
      $ncommunities = 14 - ($nitems + $nfolders + $nmaxusers);
      }

    if($nusers > 3)
      {
      $nusers = 14 - ($nitems + $nfolders + $ncommunities);
      }

    // Return the JSON results
    $results = array();
    $id = 1;
    $n = 0;
    // Items
    foreach($ItemsDao as $itemDao)
      {
      if($n == $nitems)
        {
        break;
        }
      $label = $this->Component->Utility->sliceName($itemDao->getName(), 55);
      if(isset($itemDao->count) && $itemDao->count > 1)
        {
        $label .= ' ('.$itemDao->count.')';
        }
      $result = array('id' => $id,
                      'label' => $label,
                      'value' => $itemDao->getName(),
                      'category' => $this->t('Items'));

      if(!isset($itemDao->count) || $itemDao->count == 1)
        {
        $result['itemid'] = $itemDao->getItemId();
        }
      $id++;
      $n++;
      $results[] = $result;
      }
    // Groups
    $n = 0;
    foreach($GroupsDao as $groupDao)
      {
      if($n == $ngroups)
        {
        break;
        }

      $results[] = array('id' => $id,
                         'label' => $this->Component->Utility->sliceName($groupDao->getName(), 55),
                         'value' => $groupDao->getName(),
                         'groupid' => $groupDao->getKey(),
                         'category' => $this->t('Groups'));
      $id++;
      $n++;
      }

    // Folder
    $n = 0;
    foreach($FoldersDao as $folderDao)
      {
      if($n == $nfolders)
        {
        break;
        }
      $label = $this->Component->Utility->sliceName($folderDao->getName(), 55);
      if(isset($folderDao->count) && $folderDao->count > 1)
        {
        $label .= ' ('.$folderDao->count.')';
        }
      $result = array('id' => $id,
                      'label' => $label,
                      'value' => $folderDao->getName(),
                      'category' => $this->t('Folders'));

      if(!isset($folderDao->count) || $folderDao->count == 1)
        {
        $result['folderid'] = $folderDao->getFolderId();
        }
      $id++;
      $n++;
      $results[] = $result;
      }

    // Community
    $n = 0;
    foreach($CommunitiesDao as $communityDao)
      {
      if($n == $ncommunities)
        {
        break;
        }
      $label = $this->Component->Utility->sliceName($communityDao->getName(), 55);
      if(isset($communityDao->count) && $communityDao->count > 1)
        {
        $label .= ' ('.$communityDao->count.')';
        }
      $result = array('id' => $id,
                      'label' => $label,
                      'value' => $communityDao->getName(),
                      'category' => $this->t('Communities'));

      if(!isset($communityDao->count) || $communityDao->count == 1)
        {
        $result['communityid'] = $communityDao->getKey();
        }
      $id++;
      $n++;
      $results[] = $result;
      }

    // User
    $n = 0;
    foreach($UsersDao as $userDao)
      {
      if($n == $nusers)
        {
        break;
        }
      $label = $userDao->getFirstname().' '.$userDao->getLastname();
      $value = $label;
      if(isset($userDao->count) && $userDao->count > 1)
        {
        $label .= ' ('.$userDao->count.')';
        }
      $result = array('id' => $id,
                      'label' => $label,
                      'value' => $value,
                      'category' => $this->t('Users'));

      if(!isset($userDao->count) || $userDao->count == 1)
        {
        $result['userid'] = $userDao->getKey();
        }
      $id++;
      $n++;
      $results[] = $result;
      }

    echo JsonComponent::encode($results);
    }

} // end class

