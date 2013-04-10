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
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_JS', 'getItemViewJs');
    }//end init

  /** If this object is able to be slice viewed, we show a link for that */
  public function getItemViewLink($params)
    {
    $item = $params['item'];
    if($this->ModuleComponent->Validation->canVisualizeWithSliceView($item))
      {
      $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
      $html = '<li><a class="pvwLink" type="slice"><img alt="" src="'.$webroot.'/modules/';
      $html .= $this->moduleName.'/public/images/sliceView.png" /> Slice Visualization</a></li>';

      $html .= '<li><a class="pvwLink" type="volume"><img alt="" src="'.$webroot.'/modules/';
      $html .= $this->moduleName.'/public/images/volume.png" /> Volume Visualization</a></li>';

      return $html;
      }
    else if($this->ModuleComponent->Validation->canVisualizeWithSurfaceView($item))
      {
      $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
      $html = '<li><a class="pvwLink" type="surface"><img alt="" src="'.$webroot.'/modules/';
      $html .= $this->moduleName.'/public/images/pqUnstructuredGrid16.png" /> Surface Visualization</a></li>';
      return $html;
      }
    }

  /**
   * Get the javascript that should be imported into the item view page
   */
  public function getItemViewJs()
    {
    $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
    $moduleWebroot = $webroot.'/modules/'.$this->moduleName;
    return array($webroot.'/core/public/js/layout/midas.progress.js', $moduleWebroot.'/public/js/import/item.view.js');
    }

  /** generate Dashboard information */
  public function getDashboard()
    {
    $settingModel = MidasLoader::loadModel('Setting');
    $pvpython = $settingModel->getValueByName('pvpython', 'pvw');
    $staticDir = $settingModel->getValueByName('staticcontent', 'pvw');

    // Validate pvpython setting
    if(!$pvpython)
      {
      $pvpDb = array(false, 'Must set pvpython path in module config page');
      }
    else
      {
      $pvpDb = array(is_executable($pvpython), $pvpython);
      }

    // Validate static content directory setting
    if(!$staticDir)
      {
      $staticDir = BASE_PATH.'/modules/pvw/public/pvw';
      }
    $staticDb = array(is_dir($staticDir), $staticDir);

    return array('pvpython is executable' => $pvpDb, 'Static content directory' => $staticDb);
    }
  } //end class

