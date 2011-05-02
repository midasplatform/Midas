<?php

class FolderController extends AppController
  {
  public $_models=array('Folder','Folder','Item','Folderpolicygroup','Folderpolicyuser');
  public $_daos=array('Folder','Folder','Item');
  public $_components=array('Utility','Date');
  public $_forms=array();

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = ''; // set the active menu
    $actionName=Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    if(isset($actionName) && (is_numeric($actionName) || strlen($actionName)==32)) // This is tricky! and for Cassandra for now
      {
      
      $this->_forward('view',null,null,array('folderId'=>$actionName));
      }
    $this->view->activemenu = 'browse'; // set the active menu
    }  // end init()

  /** View Action*/
  public function viewAction()
    {
    $this->view->Date=$this->Component->Date;
    $folder_id=$this->_getParam('folderId');
    $folder=$this->Folder->load($folder_id);
    $folders=array();
    $items=array();
    $header="";
    if(!isset($folder_id))
      {
      throw new Zend_Exception("Please set the folderId.");
      }
    elseif($folder===false)
      {
      throw new Zend_Exception("The folder doesn t exist.");
      }
    else
      {
      $folders=$this->Folder->getChildrenFoldersFiltered($folder,$this->userSession->Dao,MIDAS_POLICY_READ);
      $items=$this->Folder->getItemsFiltered($folder,$this->userSession->Dao,MIDAS_POLICY_READ);
      foreach($items as $key=>$i)
        {
        $items[$key]->size=$this->Component->Utility->formatSize($i->getSizebytes());
        }
      $header.=" <li class='pathFolder'><img alt='' src='{$this->view->coreWebroot}/public/images/FileTree/folder_open.png' /><span><a href='{$this->view->webroot}/folder/{$folder->getKey()}'>{$folder->getName()}</a></span></li>";
      $parent=$folder->getParent();
      while($parent!==false)
        {
        if(strpos($parent->getName(), 'community')!==false&&$this->Folder->getCommunity($parent)!==false)
          {
          $community=$this->Folder->getCommunity($parent);
          $header=" <li class='pathCommunity'><img alt='' src='{$this->view->coreWebroot}/public/images/icons/community.png' /><span><a href='{$this->view->webroot}/community/{$community->getKey()}#tabs-3'>{$community->getName()}</a></span></li>".$header;
          }
        elseif(strpos($parent->getName(), 'user')!==false&&$this->Folder->getUser($parent)!==false)
          {
          $user=$this->Folder->getUser($parent);
          $header=" <li class='pathUser'><img alt='' src='{$this->view->coreWebroot}/public/images/icons/unknownUser-small.png' /><span><a href='{$this->view->webroot}/user/{$user->getKey()}'>{$user->getFullName()}</a></span></li>".$header;
 
          }
        else
          {
          $header=" <li class='pathFolder'><img alt='' src='{$this->view->coreWebroot}/public/images/FileTree/directory.png' /><span><a href='{$this->view->webroot}/folder/{$parent->getKey()}'>{$parent->getName()}</a></span></li>".$header;
          }
        $parent=$parent->getParent();
        }
      $header="<ul class='pathBrowser'>
               <li class='pathData'><a href='{$this->view->webroot}/browse'>{$this->t('Data')}</a></li>".$header;
      $header.="</ul>";
      }
      
    $this->Folder->incrementViewCount($folder);
    $this->view->mainFolder=$folder;
    $this->view->folders=$folders;
    $this->view->items=$items;
    $this->view->header=$header;
    
    $this->view->isModerator=$this->Folder->policyCheck($folder, $this->userSession->Dao,MIDAS_POLICY_WRITE);
    $this->view->isAdmin=$this->Folder->policyCheck($folder, $this->userSession->Dao,MIDAS_POLICY_ADMIN);
    }// end View Action
    
    
   
  /** delete a folder (dialog,ajax only)*/
  public function deleteAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $folder_id=$this->_getParam('folderId');
    $folder=$this->Folder->load($folder_id);
    $header="";
    if(!isset($folder_id))
      {
      throw new Zend_Exception("Please set the folderId.");
      }
    elseif($folder===false)
      {
      throw new Zend_Exception("The folder doesn t exist.");
      }
    elseif(!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Permissions error.");
      }
      
    $parent=$folder->getParent();
    if($this->Folder->getCommunity($parent)!=false||$this->Folder->getCommunity($folder)!=false)
      {
      throw new Zend_Exception("Community Folder. You cannot delete it.");
      }
    if($this->Folder->getUser($parent)!=false||$this->Folder->getUser($folder)!=false)
      {
      throw new Zend_Exception("User Folder. You cannot delete it.");
      }
    $this->Folder->delete($folder);
    $folderInfo=$folder->_toArray();
    echo JsonComponent::encode(array(true,$this->t('Changes saved'),$folderInfo));
    }// end deleteAction
    
  /** remove an item from a folder (dialog,ajax only)*/
  public function removeitemAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $folder_id=$this->_getParam('folderId');
    $item_id=$this->_getParam('itemId');
    $folder=$this->Folder->load($folder_id);
    $item=$this->Item->load($item_id);
    $header="";
    if(!isset($folder_id))
      {
      throw new Zend_Exception("Please set the folderId.");
      }
    if(!isset($item_id))
      {
      throw new Zend_Exception("Please set the folderId.");
      }
    elseif($folder===false)
      {
      throw new Zend_Exception("The folder doesn t exist.");
      }
    elseif($item===false)
      {
      throw new Zend_Exception("The item doesn t exist.");
      }
    elseif(!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Permissions error.");
      }
      
    $this->Folder->removeItem($folder,$item);
    echo JsonComponent::encode(array(true,$this->t('Changes saved')));
    }// end deleteAction
    
  /** create a folder (dialog,ajax only)*/
  public function createfolderAction()
    {
    $this->_helper->layout->disableLayout();
    $folder_id=$this->_getParam('folderId');
    $folder=$this->Folder->load($folder_id);
    $header="";
    if(!isset($folder_id))
      {
      throw new Zend_Exception("Please set the folderId.");
      }
    elseif($folder===false)
      {
      throw new Zend_Exception("The folder doesn t exist.");
      }
    elseif(!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Permissions error.");
      }
    $this->view->parentFolder=$folder;
    if($this->_request->isPost())
      {
      $this->_helper->viewRenderer->setNoRender();
      $createFolder=$this->_getParam('createFolder');
      if(isset($createFolder))
        {
        $name=$this->_getParam('name');
        if(!isset($name))
          {
          echo JsonComponent::encode(array(false,$this->t('Error')));
          }
        else
          {
          $new_folder=$this->Folder->createFolder($name, '', $folder);
          $policyGroup=$folder->getFolderpolicygroup();
          $policyUser=$folder->getFolderpolicyuser();
          foreach($policyGroup as $policy)
            {
            $group=$policy->getGroup();
            $policyValue=$policy->getPolicy();
            $this->Folderpolicygroup->createPolicy($group,$new_folder,$policyValue);
            }
          foreach($policyUser as $policy)
            {
            $user=$policy->getUser();
            $policyValue=$policy->getPolicy();
            $this->Folderpolicyuser->createPolicy($user,$new_folder,$policyValue);
            }
            
          if($new_folder==false)
            {
            echo JsonComponent::encode(array(false,$this->t('Error')));
            }
          else
            {
            echo JsonComponent::encode(array(true,$this->t('Changes saved'),$folder->_toArray(),$new_folder->_toArray()));
            }
          }
        }
      }
    }// end createfolderAction

  }//end class