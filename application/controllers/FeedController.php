<?php

/**
 *  AJAX request for the admin Controller
 */
class FeedController extends AppController
{
  public $_models=array('Feed');
  public $_daos=array();
  public $_components=array();
    
  /** Init Controller */
  function init()
    { 
    $this->view->activemenu = 'feed'; // set the active menu
    }  // end init()  
    
  /** index Action */
  public function indexAction()
    {
    $this->view->feeds=$this->Feed->getGlobalFeeds($this->userSession->Dao);
    }
    
} // end class

  