<?php

class ItemController extends AppController
  {
  public $_models=array('Item');
  public $_daos=array();
  public $_components=array();
  public $_forms=array();

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = ''; // set the active menu
    $actionName=Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    if(isset($actionName) && is_numeric($actionName))
      {
      $this->_forward('view',null,null,array('itemId'=>$actionName));
      }
    }  // end init()


  /** view a community*/
  function viewAction()
    {
    //TODO: add policy check
    $this->view->header=$this->t("Item");
    $itemId=$this->_getParam("itemId");
    if(!isset($itemId)||!is_numeric($itemId))
      {
      throw new Zend_Exception("itemId  should be a number");
      }
    $itemDao=$this->Item->load($itemId);
    if($itemDao===false)
      {
      throw new Zend_Exception("This iutem doesn't exist.");
      }
    if(!isset($this->userSession->recentItems))
      {
      $this->userSession->recentItems=array();
      }
    
    $tmp=array_reverse($this->userSession->recentItems);
    $i=0;
    foreach($tmp as $key=>$t)
      {
      if($t->getKey()==$itemDao->getKey())
        {
        unset($tmp[$key]);
        continue;
        }
      $i++;
      if($i>10)
        {
        unset($tmp[$key]);
        }
      }
    $this->userSession->recentItems=array_reverse($tmp);
    $this->userSession->recentItems[]=$itemDao;
    $this->view->itemDao=$itemDao;
    }//end index

  }//end class