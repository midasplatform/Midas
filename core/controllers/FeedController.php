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
    }

  /** get delete a feed */
  public function deleteajaxAction()
    {
    if(!$this->getRequest()->isXmlHttpRequest() && !$this->isTestingEnv())
      {
      throw new Zend_Exception("Why are you here ? Should be ajax.");
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

