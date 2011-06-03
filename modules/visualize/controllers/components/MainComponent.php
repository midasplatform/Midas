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

class Visualize_MainComponent extends AppComponent
{ 
  /** can visualize */
  public function canVisualizeWithParaview($itemDao)
    {
    $modulesConfig=Zend_Registry::get('configsModules');
    $useparaview = $modulesConfig['visualize']->useparaview;
    if(!isset($useparaview) || !$useparaview)
      {
      return false;
      }
    
    $extensions = array('vtk', 'ply', 'vtp', 'pvsm', 'mha');
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) == 0)
      {
      return false;
      }
      
    $ext = substr(strrchr($bitstreams[0]->getName(), '.'), 1);
    return in_array($ext, $extensions);
    }//end canVisualize
    
  /* visualize*/
  public function canVisualizeTxt($itemDao)
    {
    $extensions = array('txt', 'php', 'js', 'html', 'cpp', 'java', 'py', 'h', 'log');
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) == 0)
      {
      return false;
      }
      
    $ext = substr(strrchr($bitstreams[0]->getName(), '.'), 1);
    return in_array($ext, $extensions);
    }//end canVisualize 
        
  /* visualize*/
  public function canVisualizePdf($itemDao)
    {
    $extensions = array('pdf');
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) == 0)
      {
      return false;
      }
      
    $ext = substr(strrchr($bitstreams[0]->getName(), '.'), 1);
    return in_array($ext, $extensions);
    }//end canVisualize 
    
  /* visualize*/
  public function canVisualizeImage($itemDao)
    {
    $extensions = array('jpg', 'jpeg', 'gif', 'bmp', 'png');
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) == 0)
      {
      return false;
      }
      
    $ext = substr(strrchr($bitstreams[0]->getName(), '.'), 1);
    return in_array($ext, $extensions);
    }//end canVisualize 
    
  /** can visualize */
  public function canVisualizeMedia($itemDao)
    {
    $extensions = array('m4a', 'm4v', 'mp3', 'mp4', 'avi');
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) == 0)
      {
      return false;
      }
      
    $ext = substr(strrchr($bitstreams[0]->getName(), '.'), 1);
    return in_array($ext, $extensions);
    }//end canVisualize
} // end class
?>