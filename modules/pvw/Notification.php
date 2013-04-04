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
/** notification manager */
class Pvw_Notification extends MIDAS_Notification
  {
  public $_moduleComponents = array('Validation');
  public $moduleName = 'pvw';

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDashboard');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_ACTIONMENU', 'getItemViewLink');
    }//end init

  /** If this object is able to be slice viewed, we show a link for that */
  public function getItemViewLink($params)
    {
    $item = $params['item'];
    if($this->ModuleComponent->Validation->canVisualizeWithSliceView($item))
      {
      $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
      $html = '<li><a href="'.$webroot.'/'.$this->moduleName.'/paraview/slice?itemId=';
      $html .= $item->getKey().'"><img alt="" src="'.$webroot.'/modules/';
      $html .= $this->moduleName.'/public/images/sliceView.png" /> Slice Visualization</a></li>';

      $html .= '<li><a href="'.$webroot.'/'.$this->moduleName.'/paraview/volume?itemId=';
      $html .= $item->getKey().'"><img alt="" src="'.$webroot.'/modules/';
      $html .= $this->moduleName.'/public/images/volume.png" /> Volume Visualization</a></li>';

      return $html;
      }
    else if($this->ModuleComponent->Validation->canVisualizeWithSurfaceView($item))
      {
      $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
      $html = '<li><a href="'.$webroot.'/'.$this->moduleName.'/paraview/surface?itemId=';
      $html .= $item->getKey().'"><img alt="" src="'.$webroot.'/modules/';
      $html .= $this->moduleName.'/public/images/pqUnstructuredGrid16.png" /> Surface Visualization</a></li>';
      return $html;
      }
    }

  /** generate Dasboard information */
  public function getDashboard()
    {
    // TODO 1 check that pvpython is present and is executable
    //      2 Test write permission into necessary dirs
    }
  } //end class

