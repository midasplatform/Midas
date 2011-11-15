<?php
/** notification manager*/
class Thumbnailcreator_Notification extends MIDAS_Notification
  {
  public $moduleName = 'thumbnailcreator';
  
  /** init notification process*/
  public function init()
    {
    $this->addTask("TASK_THUMBNAILCREATOR_CREATE", 'createThumbnail', "Create Thumbnail. Parameters: Item, Revision");
    $this->addEvent('EVENT_CORE_CREATE_THUMBNAIL', 'TASK_THUMBNAILCREATOR_CREATE');
    }//end init
    
  /** createThumbnail */
  public function createThumbnail($params)
    {
    $componentLoader = new MIDAS_ComponentLoader;
    $thumbnailComponent = $componentLoader->loadComponent('Imagemagick',
                                                          'Thumbnailcreator');
    $thumbnailComponent->createThumbnail($params[0]);
    return;
    }
  } //end class
?>