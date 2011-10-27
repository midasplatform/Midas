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
/** job controller*/
class Remoteprocessing_JobController extends Remoteprocessing_AppController
{
  public $_models = array('Item', 'Bitstream', 'ItemRevision', 'Assetstore');
  public $_components = array('Upload');
  public $_moduleComponents = array('Executable');
  public $_moduleModels = array('Job');

  /** manage jobs */
  function manageAction()
    {
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
    if(!$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Problem policies.");
      }
    $this->view->header = $this->t("Manage Jobs: ".$itemDao->getName());
    $metaFile = $this->ModuleComponent->Executable->getMetaIoFile($itemDao);
    $this->view->metaFile = $metaFile;
    $this->view->itemDao = $itemDao;

    $this->view->relatedJobs = $this->Remoteprocessing_Job->getRelatedJob($itemDao);

    if(isset($_GET['inprogress']))
      {
      $this->showNotificationMessage('The Job will appear in a next few minutes.');
      }
    }

  /** init a job */
  function initAction()
    {
    $this->view->header = $this->t("Job");
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
    if(!$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Problem policies.");
      }

    $metaFile = $this->ModuleComponent->Executable->getMetaIoFile($itemDao);
    if($metaFile == false)
      {
      $this->_redirect('/remoteprocessing/executable/define?init=false&itemId='.$itemDao->getKey());
      return;
      }

    $metaContent = new SimpleXMLElement(file_get_contents($metaFile->getFullPath()));
    $this->view->metaContent = $metaContent;

    $this->view->itemDao = $itemDao;
    $this->view->json['item'] = $itemDao->toArray();
    if($this->_request->isPost())
      {
      $this->disableLayout();
      $this->disableView();

      $this->ModuleComponent->Executable->initAndSchedule($itemDao, $metaContent, $_POST['results']);
      }
    }

}//end class
