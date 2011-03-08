<?php

class FolderController extends AppController
  {
  public $_models=array('Folder','Folder','Item');
  public $_daos=array('Folder','Folder','Item');
  public $_components=array('Utility','Date');
  public $_forms=array();

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = ''; // set the active menu
    $actionName=Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    if(isset($actionName) && is_numeric($actionName))
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
      $header.=" <li class='pathFolder'><a href='{$this->view->webroot}/folder/{$folder->getKey()}'>{$folder->getName()}</a></li>";
      $parent=$folder->getParent();
      while($parent!==false)
        {
        if(strpos($parent->getName(), 'community')!==false&&$this->Folder->getCommunity($parent)!==false)
          {
          $community=$this->Folder->getCommunity($parent);
          $header=" <li class='pathCommunity'><a href='{$this->view->webroot}/community/{$community->getKey()}'>{$community->getName()}</a></li>".$header;
          }
        else
          {
          $header=" <li class='pathFolder'><a href='{$this->view->webroot}/folder/{$parent->getKey()}'>{$parent->getName()}</a></li>".$header;
          }
        $parent=$parent->getParent();
        }
      $header="<ul class='pathBrowser'>
               <li class='pathData'><a href='{$this->view->webroot}/browse'>Data</a></li>".$header;
      $header.="</ul>";
      }
    $this->view->mainFolder=$folder;
    $this->view->folders=$folders;
    $this->view->items=$items;
    $this->view->header=$header;

    }// end View Action

  }//end class