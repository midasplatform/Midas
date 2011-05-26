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
    $extensions = array('vtk', 'ply', 'vtp');
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams)!=1)
      {
      return false;
      }
      
    $ext = substr(strrchr($bitstreams[0]->getName(), '.'), 1);
    return in_array($ext, $extensions);
    }//end canVisualize
} // end class
?>