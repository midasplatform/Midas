<?php
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