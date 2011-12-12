<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/
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