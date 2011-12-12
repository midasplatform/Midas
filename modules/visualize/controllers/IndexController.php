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

class Visualize_IndexController extends Visualize_AppController
{
  public $_moduleComponents=array('Main');
  public $_models=array('Item');
  
    
  /** index*/
  function indexAction()
    {
     $itemId = $this->_getParam('itemId');
    $height = $this->_getParam('height');
    $width = $this->_getParam('width');
    if(!isset($height))
      {
      $height = 500;
      }
    if(!isset($width))
      {
      $width = 500;
      }
    $itemId = $this->_getParam('itemId');
    $itemDao = $this->Item->load($itemId);
    
    if($this->ModuleComponent->Main->canVisualizeWithParaview($itemDao))
      {
      $this->_redirect('/visualize/paraview/?itemId='.$itemId.'&height='.$height.'&width='.$width);
      }
    elseif($this->ModuleComponent->Main->canVisualizeMedia($itemDao))
      {
      $this->_redirect('/visualize/media/?itemId='.$itemId.'&height='.$height.'&width='.$width);
      }
    elseif($this->ModuleComponent->Main->canVisualizeTxt($itemDao))
      {
      $this->_redirect('/visualize/txt/?itemId='.$itemId.'&height='.$height.'&width='.$width);
      }
    elseif($this->ModuleComponent->Main->canVisualizeImage($itemDao))
      {
      $this->_redirect('/visualize/image/?itemId='.$itemId.'&height='.$height.'&width='.$width);
      }
    elseif($this->ModuleComponent->Main->canVisualizePdf($itemDao))
      {
      $this->_redirect('/visualize/pdf/?itemId='.$itemId.'&height='.$height.'&width='.$width);
      }
    else
      {
      throw new Zend_Exception('Unable to visualize');
      }
    }
  } // end class
?>
