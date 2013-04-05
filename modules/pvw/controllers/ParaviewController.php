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

/** Paraview Controller */
class Pvw_ParaviewController extends Pvw_AppController
{
  public $_models = array('Item', 'ItemRevision', 'Bitstream', 'Setting');
  public $_moduleComponent = array('Paraview');
  public $_moduleModels = array('Instance');

  /**
   * This action should be invoked via XMLHttpRequest
   * to start a paraview web instance. Relevant info will
   * be returned to the client.
   * @param [appname] The name of the application to start, defaults to midas.
                      All available apps live in the 'apps' directory of this module.
   * @param itemId The id of the item to be rendered. Item data will be symlinked into the
                   location expected by pvpython
   * @return The info needed by the client to connect to the session
   */
  public function startsessionAction()
    {
    $this->disableView();
    $this->disableLayout();

    $appname = $this->_getParam('appname');
    if(!isset($appname))
      {
      $appname = 'midas';
      }
    $itemId = $this->_getParam('itemId');
    if(!isset($itemId))
      {
      throw new Zend_Exception('Must pass an itemId', 400);
      }
    $item = $this->Item->load($itemId);
    if(!$item)
      {
      throw new Zend_Exception('Invalid itemId', 404);
      }
    if(!$this->Item->policyCheck($item, $this->userSession->Dao))
      {
      throw new Zend_Exception('Read access required on item', 403);
      }

    $instance = $this->ModuleComponent->Paraview->createAndStartInstance($item, $appname);

    // TODO we should store the apache SID, pvpython process ID
    // and the port in some table for resource monitoring
    echo JsonComponent::encode(array(
      'status' => 'ok',
      'instanceId' => $instance->getKey()
      ));
    }

  /**
   * Surface (mesh) model viewer action
   * @param itemId The id of the item to view
   */
  public function surfaceAction()
    {
    $itemid = $this->_getParam('itemId');
    if(!isset($itemid))
      {
      throw new Zend_Exception('Must specify an itemId parameter');
      }
    $item = $this->Item->load($itemid);

    if($item === false || !$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception("This item doesn't exist or you don't have the permissions.");
      }
    $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/pqUnstructuredGrid16.png" />';
    $header .= ' Surface view: <a href="'.$this->view->webroot.'/item/'.$itemid.'">'.$item->getName().'</a>';
    $this->view->header = $header;

    
    }

  /**
   * Show a parallel projection slice view of the volume with locked camera controls
   * @param left The id of the item to visualize on the left
   * @param right The id of the item to visualize on the right
   * @param operations (Optional) Actions to allow from the slice view, separated by ;
   * @param jsImports (Optional) List of javascript files to import. These should contain handler
   *                             functions for imported operations. Separated by ;
   */
  public function dualAction()
    {
    $operations = $this->_getParam('operations');
    if(!isset($operations))
      {
      $operations = '';
      }

    $jsImports = $this->_getParam('jsImports');
    if(isset($jsImports))
      {
      $this->view->jsImports = explode(';', $jsImports);
      }
    else
      {
      $this->view->jsImports = array();
      }

    $left = $this->Item->load($this->_getParam('left'));
    $right = $this->Item->load($this->_getParam('right'));

    if($left === false || !$this->Item->policyCheck($left, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception("Left item doesn't exist or you don't have the permissions.");
      }
    if($right === false || !$this->Item->policyCheck($right, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception("Right item doesn't exist or you don't have the permissions.");
      }
    $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/application_tile_horizontal.png" />';
    $header .= ' Side-by-side view: <a href="'.$this->view->webroot.'/item/'.$left->getKey().'">'.$left->getName().'</a> | ';
    $header .= '<a href="'.$this->view->webroot.'/item/'.$right->getKey().'">'.$right->getName().'</a>';
    $this->view->header = $header;

    $modulesConfig = Zend_Registry::get('configsModules');
    $paraviewworkdir = $modulesConfig['visualize']->paraviewworkdir;
    $useparaview = $modulesConfig['visualize']->useparaview;
    $usesymlinks = $modulesConfig['visualize']->usesymlinks;
    $pwapp = $modulesConfig['visualize']->pwapp;
    if(!isset($useparaview) || !$useparaview)
      {
      throw new Zend_Exception('Please enable the use of a ParaViewWeb server on the module configuration page');
      }

    if(!isset($paraviewworkdir) || empty($paraviewworkdir))
      {
      throw new Zend_Exception('Please set the ParaView work directory');
      }

    $pathArray = $this->ModuleComponent->Main->createParaviewPath();
    $path = $pathArray['path'];
    $tmpFolderName = $pathArray['foderName'];

    $items = array('left' => $left, 'right' => $right);
    foreach($items as $side => $item)
      {
      $subPath = $path.'/'.$side;
      mkdir($subPath);
      $revision = $this->Item->getLastRevision($item);
      $bitstreams = $revision->getBitstreams();
      foreach($bitstreams as $bitstream)
        {
        if($usesymlinks)
          {
          symlink($bitstream->getFullPath(), $subPath.'/'.$bitstream->getName());
          }
        else
          {
          copy($bitstream->getFullPath(), $subPath.'/'.$bitstream->getName());
          }

        $ext = strtolower(substr(strrchr($bitstream->getName(), '.'), 1));
        switch($ext)
          {
          case 'mha':
            $colorArrayNames[$side] = 'MetaImage';
            break;
          case 'nrrd':
            $colorArrayNames[$side] = 'ImageFile';
            break;
          default:
            break;
          }
        if($ext != 'pvsm')
          {
          $filePaths[$side] = $subPath.'/'.$bitstream->getName();
          }
        }
      }

    $this->view->json['visualize']['urls'] = $filePaths;
    $this->view->json['visualize']['operations'] = $operations;
    $this->view->json['visualize']['colorArrayNames'] = $colorArrayNames;
    $this->view->json['visualize']['items'] = $items;
    $this->view->json['visualize']['hostname'] = $this->_getHostName();
    $this->view->json['visualize']['wsport'] = $this->_getTomcatPort($pwapp);
    $this->view->operations = $operations;
    $this->view->fileLocations = $filePaths;
    $this->view->items = $items;
    }

  /**
   * Display a volume rendering of the selected item
   * @param instanceId The id of the pvw_instance that was created with startsessionAction()
   * @param jsImports (Optional) List of javascript files to import. These should contain handler
   *                             functions for imported operations. Separated by ;
   */
  public function volumeAction()
    {
    $jsImports = $this->_getParam('jsImports');
    if(isset($jsImports))
      {
      $this->view->jsImports = explode(';', $jsImports);
      }
    else
      {
      $this->view->jsImports = array();
      }
    $this->view->instance = $this->_getPvwInstance();
    $this->_setStaticRoot($this->view->instance->getPort());

    $item = $this->view->instance->getItem();

    $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/volume.png" />';
    $header .= ' Volume rendering: <a href="'.$this->view->webroot.'/item/'.$item->getKey().'">'.$item->getName().'</a>';
    $this->view->header = $header;
    }

  /**
   * Helper function to set the static content root for pvw content in the view
   */
  private function _setStaticRoot($port)
    {
    $staticContent = $this->Setting->getValueByName('staticcontent', $this->moduleName);
    if($staticContent && is_dir($staticContent))
      {
      $this->view->staticRoot = 'http://'.$this->_getHostName().':'.$port;
      }
    else
      {
      $this->view->staticRoot = $this->view->moduleWebroot.'/public/pvw';
      }
    }

  /**
   * Helper method to get the pvw instance from the params
   */
  private function _getPvwInstance()
    {
    $instanceId = $this->_getParam('instanceId');
    if(!isset($instanceId))
      {
      throw new Zend_Exception('Must pass instanceId param', 400);
      }
    $instance = $this->Pvw_Instance->load($instanceId);
    if(!$instance)
      {
      throw new Zend_Exception('This ParaView instance no longer exists', 404);
      }
    if(!$this->ModuleComponent->Paraview->isRunning($instance))
      {
      throw new Zend_Exception('This ParaView instance has been closed.', 400);
      }
    return $instance;
    }
  /**
   * Use the axial slice view mode for MetaImage volume data
   * @param itemId The id of the MetaImage item to visualize
   * @param operations (Optional) Actions to allow from the slice view, separated by ;
   * @param jsImports (Optional) List of javascript files to import. These should contain handler
   *                             functions for imported operations. Separated by ;
   * @param meshes (Optional) List of item ids to also load into the scene as meshes
   */
  public function sliceAction()
    {
    $operations = $this->_getParam('operations');
    if(!isset($operations))
      {
      $operations = '';
      }

    $jsImports = $this->_getParam('jsImports');
    if(isset($jsImports))
      {
      $this->view->jsImports = explode(';', $jsImports);
      }
    else
      {
      $this->view->jsImports = array();
      }

    $meshes = $this->_getParam('meshes');
    if(isset($meshes))
      {
      $meshes = explode(';', $meshes);
      }
    else
      {
      $meshes = array();
      }

    $itemid = $this->_getParam('itemId');
    $item = $this->Item->load($itemid);
    if($item === false || !$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ))
      {
      throw new Zend_Exception("This item doesn't exist or you don't have the permissions.");
      }
    $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/sliceView.png" />';
    $header .= ' Slice view: <a href="'.$this->view->webroot.'/item/'.$itemid.'">'.$item->getName().'</a>';
    $this->view->header = $header;

    $modulesConfig = Zend_Registry::get('configsModules');
    $paraviewworkdir = $modulesConfig['visualize']->paraviewworkdir;
    $useparaview = $modulesConfig['visualize']->useparaview;
    $usesymlinks = $modulesConfig['visualize']->usesymlinks;
    $pwapp = $modulesConfig['visualize']->pwapp;
    if(!isset($useparaview) || !$useparaview)
      {
      throw new Zend_Exception('Please enable the use of a ParaViewWeb server on the module configuration page');
      }

    if(!isset($paraviewworkdir) || empty($paraviewworkdir))
      {
      throw new Zend_Exception('Please set the ParaView work directory');
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
      switch($ext)
        {
        case 'mha':
          $colorArrayName = 'MetaImage';
          break;
        case 'nrrd':
          $colorArrayName = 'ImageFile';
          break;
        default:
          break;
        }
      if($ext != 'pvsm')
        {
        $filePath = $paraviewworkdir.'/'.$tmpFolderName.'/'.$bitstream->getName();
        }
      }

    // Load in other mesh sources
    $meshObj = array();
    foreach($meshes as $meshId)
      {
      $otherItem = $this->Item->load($meshId);
      if($otherItem === false || !$this->Item->policyCheck($otherItem, $this->userSession->Dao, MIDAS_POLICY_READ))
        {
        throw new Zend_Exception("This item doesn't exist or you don't have the permissions.");
        }
      $revision = $this->Item->getLastRevision($otherItem);
      $bitstreams = $revision->getBitstreams();
      foreach($bitstreams as $bitstream)
        {
        $otherFile = $path.'/'.$bitstream->getName();
        if($usesymlinks)
          {
          symlink($bitstream->getFullPath(), $otherFile);
          }
        else
          {
          copy($bitstream->getFullPath(), $otherFile);
          }
        }
      // Use metadata values for mesh color and orientation if they exist
      $metadata = $this->ItemRevision->getMetadata($revision);
      $diffuseColor = array(1.0, 0.0, 0.0); //default to red mesh
      $orientation = array(0.0, 0.0, 0.0); //default to no orientation transform
      foreach($metadata as $metadatum)
        {
        if(strtolower($metadatum->getElement()) == 'visualize')
          {
          if(strtolower($metadatum->getQualifier()) == 'diffusecolor')
            {
            try //must be json encoded, otherwise we ignore it and use the default
              {
              $diffuseColor = json_decode($metadatum->getValue());
              }
            catch(Exception $e)
              {
              $this->getLogger()->warn('Invalid diffuseColor metadata value (id='.$meshId.')');
              }
            }
          if(strtolower($metadatum->getQualifier()) == 'orientation')
            {
            try //must be json encoded, otherwise we ignore it and use the default
              {
              $orientation = json_decode($metadatum->getValue());
              }
            catch(Exception $e)
              {
              $this->getLogger()->warn('Invalid orientation metadata value (id='.$meshId.')');
              }
            }
          }
        }
      $meshObj[] = array('path' => $otherFile, 'item' => $otherItem, 'visible' => true,
                         'diffuseColor' => $diffuseColor,
                         'orientation' => $orientation);
      }

    $this->view->json['visualize']['url'] = $filePath;
    $this->view->json['visualize']['operations'] = $operations;
    $this->view->json['visualize']['meshes'] = $meshObj;
    $this->view->json['visualize']['colorArrayName'] = $colorArrayName;
    $this->view->json['visualize']['item'] = $item;
    $this->view->json['visualize']['hostname'] = $this->_getHostName();
    $this->view->json['visualize']['wsport'] = $this->_getTomcatPort($pwapp);
    $this->view->operations = $operations;
    $this->view->fileLocation = $filePath;
    $this->view->itemDao = $item;
    }

  /**
   * Helper method to pass the server host name to json for using web socket renderer
   */
  protected function _getHostName()
    {
    if($this->isTestingEnv())
      {
      return 'localhost';
      }
    else
      {
      return empty($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['HTTP_X_FORWARDED_HOST'];
      }
    }

} // end class
?>
