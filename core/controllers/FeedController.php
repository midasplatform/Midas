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
 *  AJAX request for the admin Controller
 */
class FeedController extends AppController
{
  public $_models = array('Feed', 'Item', 'User', 'Community');
  public $_daos = array();
  public $_components = array();

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = 'feed'; // set the active menu
    }  // end init()

  /** index Action */
  public function indexAction()
    {
    $this->view->feeds = $this->Feed->getGlobalFeeds($this->userSession->Dao);
    $this->view->itemThumbnails = $this->Item->getRandomThumbnails($this->userSession->Dao, 0, 12, true);
    $this->view->nUsers = $this->User->getCountAll();
    $this->view->nCommunities = $this->Community->getCountAll();
    $this->view->nItems = $this->Item->getCountAll();
    $this->view->notifications = array();
    $this->view->header = $this->t('Feed');

    if($this->logged && !$this->isTestingEnv())
      {
      $request = $this->getRequest();
      $cookieData = $request->getCookie('newFeed'.$this->userSession->Dao->getKey());
      if(isset($cookieData) && is_numeric($cookieData))
        {
        $this->view->lastFeedVisit = $cookieData;
        }
      setcookie('newFeed'.$this->userSession->Dao->getKey(), strtotime("now"), time() + 60 * 60 * 24 * 300, '/'); //30 days
      }

    $this->addDynamicHelp('.feedContainer', 'The <b>Feed</b> shows recent actions and events.', 'top right', 'bottom left');
    }

  /** get delete a feed */
  public function deleteajaxAction()
    {
    if(!$this->isTestingEnv())
      {
      $this->requireAjaxRequest();
      }

    $this->disableLayout();
    $this->disableView();

    $feedId = $this->_getParam('feed');
    if(!isset($feedId) || (!is_numeric($feedId) && strlen($feedId) != 32)) // This is tricky! and for Cassandra for now)
      {
      throw new Zend_Exception("Please set the feed Id");
      }
    $feed = $this->Feed->load($feedId);

    if($feed == false)
      {
      return;
      }

    if(!$this->Feed->policyCheck($feed, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      return;
      }
    $this->Feed->delete($feed);
    }//end deleteajaxAction

} // end class

