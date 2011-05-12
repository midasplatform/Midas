<?php
/** Item Controller */
class ItemController extends AppController
  {
  public $_models = array('Item', 'ItemRevision', 'Bitstream');
  public $_daos = array();
  public $_components = array('Date', 'Utility', 'Sortdao');
  public $_forms = array();

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = ''; // set the active menu
    $actionName = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    if(isset($actionName) && (is_numeric($actionName) || strlen($actionName) == 32)) // This is tricky! and for Cassandra for now
      {
      $this->_forward('view', null, null, array('itemId' => $actionName));
      }
    }  // end init()


  /** view a community*/
  function viewAction()
    {
    $this->view->header = $this->t("Item");
    $this->view->Date = $this->Component->Date;
    $this->view->Utility = $this->Component->Utility;
    $itemId = $this->_getParam("itemId");
    if(!isset($itemId) || !is_numeric($itemId))
      {
      throw new Zend_Exception("itemId  should be a number");
      }
    $itemDao = $this->Item->load($itemId);
    if($itemDao === false)
      {
      throw new Zend_Exception("This item doesn't exist.");
      }
    if(!$this->Item->policyCheck($itemDao, $this->userSession->Dao))
      {
      throw new Zend_Exception("Problem policies.");
      }
      
    $this->view->isAdmin = $this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
    $this->view->isModerator = $this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_WRITE);

    if($this->logged)
      {
      $request = $this->getRequest();
      $cookieData = $request->getCookie('recentItems'.$this->userSession->Dao->getKey());
      $recentItems = array();
      if(isset($cookieData))
        {
        $recentItems = unserialize($cookieData); 
        }    
      $tmp = array_reverse($recentItems);
      $i = 0;
      foreach($tmp as $key => $t)
        {
        if($t['item_id'] == $itemDao->getKey())
          {
          unset($tmp[$key]);
          continue;
          }
        $i++;
        if($i > 10)
          {
          unset($tmp[$key]);
          }
        }
      $recentItems = array_reverse($tmp);
      $itemDaoArray['item_id'] = $itemDao->getKey();
      $itemDaoArray['name'] = $itemDao->getName();
      $recentItems[] = $itemDaoArray;

      setcookie('recentItems'.$this->userSession->Dao->getKey(), serialize($recentItems), time() + 60 * 60 * 24 * 30, '/'); //30 days
      }
    $itemRevision = $this->Item->getLastRevision($itemDao);
    $this->Item->incrementViewCount($itemDao);
    $itemDao->lastrevision = $itemRevision;
    $itemDao->revisions = $itemDao->getRevisions();
    
    $this->Component->Sortdao->field = 'revision';
    $this->Component->Sortdao->order = 'desc';
    usort($itemDao->revisions, array($this->Component->Sortdao, 'sortByNumber'));
    
    $itemDao->creation = $this->Component->Date->formatDate(strtotime($itemRevision->getDate()));
    $this->view->itemDao = $itemDao;
    
    $this->view->itemSize = $this->Component->Utility->formatSize($itemDao->getSizebytes());
    
    $this->view->json['item'] = $itemDao->toArray();
    $this->view->json['item']['message']['delete'] = $this->t('Delete');
    $this->view->json['item']['message']['deleteMessage'] = $this->t('Do you really want to delete this item? It cannot be undo.');
    $this->view->json['item']['message']['movecopy'] = $this->t('Copy Item.');
    }//end index

    
  /** Delete an item*/
  function deleteAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    
    $itemId = $this->_getParam("itemId");
    if(!isset($itemId) || (!is_numeric($itemId) && strlen($itemId) != 32)) // This is tricky! and for Cassandra for now
      {
      throw new Zend_Exception("itemId should be a number");
      }
    $itemDao = $this->Item->load($itemId);
    if($itemDao === false || !$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception("This community doesn't exist or you don't have the permissions.");
      }
      
    $this->Item->delete($itemDao);

    $this->_redirect('/?checkRecentItem = true');
    }//end delete
    
    
  /** Merge items*/
  function mergeAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    
    $itemIds = $this->_getParam("items");
    $name = $this->_getParam("name");
    if(empty($name))
      {
      throw new Zend_Exception('Please set a name');
      }
    $itemIds = explode('-', $itemIds);
    
    $items = array();
    foreach($itemIds as $item)
      {
      $itemDao = $this->Item->load($item);
      if($itemDao != false && $this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
        {
        $items[] = $itemDao;
        }
      }
      
    if(empty($items))
      {
      throw new Zend_Exception('Permissions error');
      }
      
      
    $mainItem = $items[0];
    $mainItemLastResision = $this->Item->getLastRevision($mainItem);
    foreach($items as $key => $item)
      {
      if($key != 0)
        {
        $revision = $this->Item->getLastRevision($item);
        $bitstreams = $revision->getBitstreams();
        foreach($bitstreams as $b)
          {
          $b->setItemrevisionId($mainItemLastResision->getKey());
          $this->Bitstream->save($b);
          }
        $this->Item->delete($item);
        }
      }
      
    $mainItem->setSizebytes($this->ItemRevision->getSize($mainItemLastResision));
    $mainItem->setName($name);
    $this->Item->save($mainItem);
    
    $this->_redirect('/browse/uploaded');
    }//end delete
    
  }//end class
  
  /*    pour la récupérer 
     */