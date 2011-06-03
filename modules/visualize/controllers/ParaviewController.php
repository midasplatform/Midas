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

class Visualize_ParaviewController extends Visualize_AppController
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
      
    if(!file_exists(BASE_PATH.'/tmp/visualize'))
      {
      mkdir(BASE_PATH.'/tmp/visualize');
      }
     
    do
      {
      $tmpFolderName = 'ParaviewWeb_'.mt_rand(0, 9999999);
      $path = BASE_PATH.'/tmp/visualize/'.$tmpFolderName;
      }
    while (!mkdir($path, '700'));
    
    $revision = $this->Item->getLastRevision($item);
    $bitstreams = $revision->getBitstreams();
    foreach($bitstreams as $bitstream)
      {
      copy($bitstream->getFullPath(), $path.'/'.$bitstream->getName());
      $ext = substr(strrchr($bitstream->getName(), '.'), 1);
      if($ext != 'pvsm')
        {
        $filePath = "/workspace/PW-work/midas/".$tmpFolderName.'/'.$bitstream->getName();
        $mainBitstream = $bitstream;
        }
      }   
    
    $this->view->json['visualize']['openState'] = false;
      
    foreach($bitstreams as $bitstream)
      {
      $ext = substr(strrchr($bitstream->getName(), '.'), 1);
      if($ext == 'pvsm')
        {        
        $file_contents = file_get_contents($path.'/'.$bitstream->getName());
        $file_contents = preg_replace('/\"([a-zA-Z0-9_.\/\\\:]{1,1000})'.  str_replace('.', '\.', $mainBitstream->getName())."/", '"'.$filePath, $file_contents);
        $filePath = "/workspace/PW-work/midas/".$tmpFolderName.'/'.$bitstream->getName();
        $inF = fopen($path.'/'.$bitstream->getName(),"w");
        fwrite($inF, $file_contents);
        fclose($inF); 
        $this->view->json['visualize']['openState'] = true;
        break;
        }
      }  
      
    
    if($item->getSizebytes()> 1*1024*1024)
      {
      $this->view->renderer = 'js';
      }
    else
      {
      $this->view->renderer = 'webgl';
      }
      
    $this->view->json['visualize']['url'] = $filePath;    
    $this->view->json['visualize']['width'] = $this->_getParam('width');    
    $this->view->json['visualize']['height'] = $this->_getParam('height');    
    }
} // end class
?>
