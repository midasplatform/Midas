<?php

/**
 *  AJAX request for the admin Controller
 */
class FeedController extends AppController
{
  public $_models=array('Feed','Item','User','Community');
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
    $this->view->itemThumbnails=$this->Item->getRandomItems($this->userSession->Dao,0,12,true);
    $this->view->nUsers=$this->User->getCountAll();
    $this->view->nCommunities=$this->Community->getCountAll();
    $this->view->nItems=$this->Item->getCountAll();
    $this->view->notifications=array();
    $this->view->header=$this->t('Feed');
    }
    
     /** get getfolders Items' size */
  public function deleteajaxAction()
    {
    if(!$this->getRequest()->isXmlHttpRequest())
     {
     throw new Zend_Exception("Why are you here ? Should be ajax.");
     }     
     
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    
    $feedId=$this->_getParam('feed');
    if(!isset($feedId) || (!is_numeric($feedId) && strlen($feedId)!=32)) // This is tricky! and for Cassandra for now)
     {
     throw new Zend_Exception("Please set the feed Id");
     }
    $feed= $this->Feed->load($feedId);
    if($feed==false)
      {
      return;
      }    
    if(!$this->Feed->policyCheck($feed,$this->userSession->Dao,2))
      {
      return;
      }
    $this->Feed->delete($feed);      
    }//end getfolderscontent
    
} // end class

  