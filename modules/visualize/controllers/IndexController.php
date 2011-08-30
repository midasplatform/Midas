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
    
    $modulesConfig = Zend_Registry::get('configsModules');
    $revision = $this->Item->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    if(isset($modulesConfig['slicer']) && count($bitstreams) == 1 && $bitstreams[0]->getName() == 'slicer.html')
      {
      $this->_redirect('/slicer/visualize/?itemId='.$itemId.'&height=500&width=800');
      }
    
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
