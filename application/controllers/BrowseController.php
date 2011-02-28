<?php

/**
 *  AJAX request for the admin Controller
 */
class BrowseController extends AppController
{
  public $_models=array('Folder','User','Community','Folder','Item');
  public $_daos=array('Folder','User','Community','Folder','Item');
  public $_components=array('Date','Utility');

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = 'browse'; // set the active menu
    }  // end init()

  /** Index Action*/
  public function indexAction()
    {
    $communities=array();
    $items=array();
    $header="";

    $communities=$this->User->getUserCommunities($this->userSession->Dao);
    $header.="> Data";
    
    $this->view->Date=$this->Component->Date;
    
    $this->view->communities=$communities;
    $this->view->header=substr($header,2);

    $javascriptText=array();
    $javascriptText['view']=$this->t('View');
    $javascriptText['edit']=$this->t('Edit');
    $javascriptText['delete']=$this->t('Delete');
    $javascriptText['share']=$this->t('Share');
    $javascriptText['rename']=$this->t('Rename');
    $javascriptText['move']=$this->t('Move');
    $javascriptText['copy']=$this->t('Copy');

    $javascriptText['community']['invit']=$this->t('Invite collaborators');
    $javascriptText['community']['advanced']=$this->t('Advanced properties');
    $this->view->json['browse']=$javascriptText;
    }

  /** get getfolders content (ajax function for the treetable) */
  public function getfolderscontentAction()
    {
    if(!$this->getRequest()->isXmlHttpRequest())
     {
     throw new Zend_Exception("Why are you here ? Should be ajax.");
     }
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $folderIds=$this->_getParam('folders');
    if(!isset($folderIds))
     {
     throw new Zend_Exception("Please set the folder Id");
     }
    $folderIds=explode('-',$folderIds);
    $parents= $this->Folder->load($folderIds);
    if(empty($parents))
      {
      throw new Zend_Exception("Folder doesn't exist");
      }
    $folders=$this->Folder->getChildrenFoldersFiltered($parents,$this->userSession->Dao,MIDAS_POLICY_READ);
    $items=$this->Folder->getItemsFiltered($parents,$this->userSession->Dao,MIDAS_POLICY_READ);
    $jsonContent=array();
    foreach ($folders as $folder)
      {
      $tmp=array();
      $tmp['folder_id']=$folder->getFolderId();
      $tmp['name']=$folder->getName();
      $tmp['creation']=$this->Component->Date->ago($folder->getDate(),true);
      $jsonContent[$folder->getParentId()]['folders'][]=$tmp;
      unset($tmp);
      }
    foreach ($items as $item)
      {
      $tmp=array();
      $tmp['item_id']=$item->getItemId();
      $tmp['name']=$item->getName();
      $tmp['parent_id']=$item->parent_id;
      $itemRevision=$this->Item->getLastRevision($item);
      $tmp['creation']=$this->Component->Date->ago($itemRevision->getDate(),true);
      $tmp['size']=$this->Component->Utility->formatSize($item->getSizebytes());
      $jsonContent[$item->parent_id]['items'][]=$tmp;
      unset($tmp);
      }
    echo JsonComponent::encode($jsonContent);
    }//end getfolderscontent
    
   /** get getfolders Items' size */
   public function getfolderssizeAction()
    {
    if(!$this->getRequest()->isXmlHttpRequest())
     {
     throw new Zend_Exception("Why are you here ? Should be ajax.");
     }     
     
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $folderIds=$this->_getParam('folders');
    if(!isset($folderIds))
     {
     throw new Zend_Exception("Please set the folder Id");
     }
    $folderIds=explode('-',$folderIds);
    $folders= $this->Folder->load($folderIds);
    $folders=$this->Folder->getSizeFiltered($folders,$this->userSession->Dao);
    $return=array();
    foreach($folders as $folder)
      {
      $return[]=array('id'=>$folder->getKey(),'count'=>$folder->count,'size'=>$this->Component->Utility->formatSize($folder->size));
      }
    echo JsonComponent::encode($return);
    }//end getfolderscontent

   /** get element info (ajax function for the treetable) */
  public function getelementinfoAction()
    {
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception("Why are you here ? Should be ajax.");
      }
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $element=$this->_getParam('type');
    $id=$this->_getParam('id');
    if(!isset($id)||!isset($element))
     {
     throw new Zend_Exception("Please double check the parameters");
     }
    $jsonContent=array('type'=>$element);
    switch ($element)
      {
      case 'community':
        $community=$this->Community->load($id);
        $jsonContent=array_merge($jsonContent,$community->_toArray());
        break;
      case 'folder':
        $folder=$this->Folder->load($id);
        $jsonContent=array_merge($jsonContent,$folder->_toArray());
        $jsonContent['creation']=$jsonContent['date'];
        break;
      case 'item':
        $item=$this->Item->load($id);
        $jsonContent=array_merge($jsonContent,$item->_toArray());
        $itemRevision=$this->Item->getLastRevision($item);
        $jsonContent['creation']=$itemRevision->getDate();
        break;
      default:
        throw new Zend_Exception("Please select the right type of element.");
        break;
      }
    $jsonContent['translation']['Created']=$this->t('Created');
    echo JsonComponent::encode($jsonContent);
    }//end getElementInfo


        /** review (browse) uploaded files*/
    public function uploadedAction()
      {
      if(empty($this->userSession->uploaded)||!$this->logged)
        {
        $this->_redirect('/');
        }
      $this->view->items=array();
      foreach($this->userSession->uploaded as $item)
        {
        $this->view->items[]=$this->Item->load($item);
        }
      }
} // end class

