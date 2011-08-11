<?php

class Scheduler_ConfigController extends Scheduler_AppController
{
   public $_moduleForms=array('Config');
   public $_components=array('Utility', 'Date');
   public $_moduleModels=array('Job', 'JobLog');

   
   /** index action*/
   function indexAction()
    {
    if(!$this->logged||!$this->userSession->Dao->getAdmin()==1)
      {
      throw new Zend_Exception("You should be an administrator");
      }

    $this->view->jobs = $this->Scheduler_Job->getJobsToRun();
    $this->view->jobsErrors = $this->Scheduler_Job->getLastErrors();
    } 
    
    
}//end class