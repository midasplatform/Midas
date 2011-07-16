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

/** notification manager*/
class Notification extends MIDAS_Notification
  {
  public $_components = array('Utility');
  public $_models = array('User', 'Item');
  
  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDasboard');
    $this->addTask('TASK_CORE_RESET_ITEM_INDEXES', 'resetItemIndexes', 'Recompute lucene indexes');
    }//end init  
    
  /** generate Dasboard information */
  public function getDasboard()
    {
    $return = array();
    $return['Database'] = array(true); //If you are here it works...
    $return['Image Magick'] = array($this->Component->Utility->isImageMagickWorking());
    $return['Config Folder Writable'] = array(is_writable(BASE_PATH.'/core/configs'));
    $return['Data Folder Writable'] = array(is_writable(BASE_PATH.'/data'));
    $return['Temporary Folder Writable'] = array(is_writable(BASE_PATH.'/tmp'));
    
    return $return;
    }//end _getDasboard
    
    
  /** reset item indexes */
  public function resetItemIndexes()
    {
    $users = $this->User->getAll();
    foreach($users as $user)
      {
      $items = $this->Item->getOwnedByUser($user, 999999);
      foreach($items as $item)
        {
        $this->Item->save($item);
        }
      }
      
    require_once BASE_PATH.'/core/controllers/components/SearchComponent.php';
    $component = new SearchComponent();    
    $index = $component->getLuceneItemIndex();
    
    $index->optimize();
    }
  } //end class
?>