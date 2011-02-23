<?php

class FolderController extends AppController
  {
  public $_models=array('Folder','Folder','Item');
  public $_daos=array('Folder','Folder','Item');
  public $_components=array();
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
    }  // end init()

  /** View Action*/
  public function viewAction()
    {
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
      $header.="> {$folder->getName()}";
      $parent=$folder->getParent();
      while($parent!==false)
        {
        if(strpos($parent->getName(), 'community')!==false&&$this->Folder->getCommunity($parent)!==false)
          {
          $community=$this->Folder->getCommunity($parent);
          $header="> {$community->getName()} ".$header;
          }
        else
          {
          $header="> {$parent->getName()} ".$header;
          }
        $parent=$parent->getParent();
        }
      }
    $this->view->folders=$folders;
    $this->view->items=$items;
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
    }// end View Action

  }//end class