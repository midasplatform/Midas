<?php
/**
 * TaskController
 * 
 */
class TaskController extends AppController
{

  public $_models=array('Task','Item');
  public $_daos=array();
  public $_components=array('Filter');
  
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
             $this->Task->delete($task);
             break;
             }
           }
         break;
       default:
         break;
       }
     }
   } // end method indexAction

   function indexAction()
  {
  } 
    
}//end class