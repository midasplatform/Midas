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

class Visualize_ParaviewController extends Visualize_AppController
{
  public $_models = array('Item', 'ItemRevision', 'Bitstream');
  public $_moduleComponents=array('Main');
  
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
    
    $modulesConfig=Zend_Registry::get('configsModules');
    $paraviewworkdir = $modulesConfig['visualize']->paraviewworkdir;
    $customtmp = $modulesConfig['visualize']->customtmp;    
    $useparaview = $modulesConfig['visualize']->useparaview;
    $userwebgl = $modulesConfig['visualize']->userwebgl;
    $usesymlinks = $modulesConfig['visualize']->usesymlinks;
    $pwapp = $modulesConfig['visualize']->pwapp;
    if(!isset($useparaview) || !$useparaview)
      {
      throw new Zend_Exception('Please unable paraviewweb');
      }
      
    if(!isset($paraviewworkdir) || empty($paraviewworkdir))
      {
      throw new Zend_Exception('Please set the paraview work directory');
      }    
   
      
    $pathArray = $this->ModuleComponent->Main->createParaviewPath();
    $path = $pathArray['path'];
    $tmpFolderName = $pathArray['foderName'];
    
    $revision = $this->Item->getLastRevision($item);
    $bitstreams = $revision->getBitstreams();
    foreach($bitstreams as $bitstream)
      {
      if($usesymlinks)
        {
        symlink($bitstream->getFullPath(), $path.'/'.$bitstream->getName());
        }
      else
        {
        copy($bitstream->getFullPath(), $path.'/'.$bitstream->getName());
        }
        
      $ext = strtolower(substr(strrchr($bitstream->getName(), '.'), 1));
      if($ext != 'pvsm')
        {
        $filePath = $paraviewworkdir."/".$tmpFolderName.'/'.$bitstream->getName();
        $mainBitstream = $bitstream;
        }
      }   
    
    $this->view->json['visualize']['openState'] = false;
      
    foreach($bitstreams as $bitstream)
      {
      $ext = strtolower(substr(strrchr($bitstream->getName(), '.'), 1));
      if($ext == 'pvsm')
        {        
        $file_contents = file_get_contents($path.'/'.$bitstream->getName());
        $file_contents = preg_replace('/\"([a-zA-Z0-9_.\/\\\:]{1,1000})'.  str_replace('.', '\.', $mainBitstream->getName())."/", '"'.$filePath, $file_contents);
        $filePath = $paraviewworkdir."/".$tmpFolderName.'/'.$bitstream->getName();
        $inF = fopen($path.'/'.$bitstream->getName(),"w");
        fwrite($inF, $file_contents);
        fclose($inF); 
        $this->view->json['visualize']['openState'] = true;
        break;
        }
      }  
      
    
    if(!$userwebgl || $item->getSizebytes()> 1*1024*1024)
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
    $this->view->width = $this->_getParam('width');    
    $this->view->height = $this->_getParam('height');   
    $this->view->fileLocation= $filePath;
    $this->view->pwapp= $pwapp;
    $this->view->usewebgl = $userwebgl;
    $this->view->itemDao = $item;
    $this->view->screenshotPath = '/data/visualize/_'.$item->getKey().'_1.png';
    $this->view->useScreenshot = file_exists(BASE_PATH.$this->view->screenshotPath);
    $this->view->loadState = $this->view->json['visualize']['openState'];
    }
    
  
} // end class
?>
