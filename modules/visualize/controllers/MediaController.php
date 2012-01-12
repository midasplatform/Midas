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

class Visualize_MediaController extends Visualize_AppController
{
  public $_models = array('Item', 'ItemRevision', 'Bitstream');
  /** index */
  public function indexAction()
    {
    $this->_helper->layout->disableLayout();
    $itemid = $this->_getParam('itemId');
    $item = $this->Item->load($itemid);
    
    if($item === false || !$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception("This item doesn't exist  or you don't have the permissions.");
      }   
    
    $revision = $this->Item->getLastRevision($item);
    $bitstreams = $revision->getBitstreams();    
    if(count($bitstreams) != 1)
      {
      throw new Zend_Exception('Error');
      }
    $this->bistream = $bitstreams[0];
    
    $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));
    if(in_array($ext, array('avi', 'mp4', 'm4v')))
      {
      $this->view->json['type'] = 'm4v';
      }
    elseif(in_array($ext, array('mp3')))
      {
      $this->view->json['type'] = 'mp3';
      }
    else
      {
      $this->view->json['type'] = 'm4a';
      }      
    $this->view->json['itemId'] = $item->getKey();
    }
} // end class
?>
