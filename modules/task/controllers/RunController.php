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

/**
 * TaskController
 * 
 */
class Task_RunController extends Task_AppController
{

  public $_models=array('Task','Item');
  public $_moduleModels=array();
  public $_daos=array();
  public $_moduleDaos=array();
  public $_components=array('Filter');
  public $_moduleComponents=array();
  public $_forms=array();
  public $_moduleForms=array();
  
  /**
   * @method initAction()
   *  Index Action (first action when we access the application)
   */
  function init()
   {         
   $this->_helper->layout->disableLayout();
   $this->_helper->viewRenderer->setNoRender();
     
   $tasks=$this->Task->getAll();
   echo "\n\n".date('c')."\n";
   echo count($tasks)." tasks\n";
   foreach($tasks as $task)
     {
     $type=$task->getType();

     switch ($type)
       {
       case MIDAS_TASK_ITEM_THUMBNAIL:
         echo " MIDAS_TASK_ITEM_THUMBNAIL item {$task->getResourceId()}\n";
         $item=$this->Item->load($task->getResourceId());
         $revision=$this->Item->getLastRevision($item);
         $bitstreams=$revision->getBitstreams();
         $thumbnailCreator=$this->Component->Filter->getFilter('ThumbnailCreator');
         foreach($bitstreams as $bitstream)
           {
           $thumbnailCreator->inputFile = $bitstream->getPath();
           $thumbnailCreator->inputName = $bitstream->getName();
           $hasThumbnail = $thumbnailCreator->process();
           $thumbnail_output_file = $thumbnailCreator->outputFile;
           if($hasThumbnail&&  file_exists($thumbnail_output_file))
             {
             $oldThumbnail=$item->getThumbnail();
             if(!empty($oldThumbnail))
                {
                unlink($oldThumbnail);
                }
             $item->setThumbnail(substr($thumbnail_output_file, strlen(BASE_PATH)+1));
             $this->Item->save($item);
             
             break;
             }
           }
         break;
       default:
         break;
       }
     $this->Task->delete($task);
     }
   } // end method indexAction

   function indexAction()
  {

  } 
    
}//end class