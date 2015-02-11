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
    public function init()
    {
        $this->view->activemenu = 'feed'; // set the active menu
    }

    /** index Action */
    public function indexAction()
    {
        $this->view->feeds = $this->Feed->getGlobalFeeds($this->userSession->Dao);
        $this->view->loggedUser = $this->userSession->Dao;
        $this->view->itemThumbnails = $this->Item->getRandomThumbnails($this->userSession->Dao, 0, 12, true);
        $this->view->nUsers = $this->User->getCountAll();
        $this->view->nCommunities = $this->Community->getCountAll();
        $this->view->nItems = $this->Item->getCountAll();
        $this->view->notifications = array();
        $this->view->header = $this->t('Feed');

        if ($this->logged && !$this->isTestingEnv()) {
            $cookieName = hash('sha1', MIDAS_FEED_COOKIE_NAME.$this->userSession->Dao->getKey());

            /** @var Zend_Controller_Request_Http $request */
            $request = $this->getRequest();
            $cookieData = $request->getCookie($cookieName);

            if (isset($cookieData) && is_numeric($cookieData)) {
                $this->view->lastFeedVisit = $cookieData;
            }
            $date = new DateTime();
            $interval = new DateInterval('P1M');
            setcookie(
                $cookieName,
                $date->getTimestamp(),
                $date->add($interval)->getTimestamp(),
                '/',
                $request->getHttpHost(),
                (int) Zend_Registry::get('configGlobal')->get('cookie_secure', 0) === 1,
                true
            );
        }

        $this->addDynamicHelp(
            '.feedContainer',
            'The Feed shows recent actions and events.',
            'top right',
            'bottom left'
        );
    }

    /** get delete a feed */
    public function deleteajaxAction()
    {
        if (!$this->isTestingEnv()) {
            $this->requireAjaxRequest();
        }

        $this->disableLayout();
        $this->disableView();

        $feedId = $this->getParam('feed');
        if (!isset($feedId) || !is_numeric($feedId)) {
            throw new Zend_Exception("Please set the feed Id");
        }
        $feed = $this->Feed->load($feedId);

        if ($feed == false) {
            return;
        }

        if (!$this->Feed->policyCheck($feed, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            return;
        }
        $this->Feed->delete($feed);
    }
}
