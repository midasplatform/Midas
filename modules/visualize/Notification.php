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
/** notification manager*/
class Visualize_Notification extends MIDAS_Notification
  {
  public $_moduleComponents = array('Main');
  public $moduleName = 'visualize';

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDashboard');
    $this->addCallBack('CALLBACK_VISUALIZE_CAN_VISUALIZE', 'canVisualize');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_ACTIONMENU', 'getItemViewLink');

    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_BUTTON', 'getButton');

    $this->addTask('TASK_VISUALIZE_PROCESSDATA', 'processParaviewData', "Create Screenshots and get Metadata. Parameters: Item, Revision");
    $this->addEvent('EVENT_CORE_CREATE_THUMBNAIL', 'TASK_VISUALIZE_PROCESSDATA');

    $this->addTask('TASK_CREATE_THREEJS_OBJECT', 'createThreejsObject', "Convert a vtk file to threejs binary file");
    $this->addEvent('EVENT_CORE_UPLOAD_FILE', 'TASK_CREATE_THREEJS_OBJECT');
    }//end init

  /** Get the link to place in the item action menu */
  public function getButton($params)
    {
    if($this->canVisualize($params))
      {
      $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
      $html = '<div style="float:right;position:absolute;top:51px;">';
      $html .= '<button class="topDownloadButton" onclick="window.location.href=\''.$webroot.
        '/visualize/wrapper/?itemId='.$params['item']->getKey().'\'">';
      $html .= $this->t('Preview');
      $html .= "</button>";
      $html .= '</div>';

      return $html;
      }

    return "";
    }

  /** If this object is able to be slice viewed, we show a link for that */
  public function getItemViewLink($params)
    {
    $item = $params['item'];
    if($this->ModuleComponent->Main->canVisualizeWithSliceView($item))
      {
      $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
      return '<li><a href="'.$webroot.'/visualize/paraview/slice?itemId='.
        $item->getKey().'"><img alt="" src="'.$webroot.'/modules/'.
        $this->moduleName.'/public/images/sliceView.png" /> Slice Visualization</a></li>';
      }
    }

  /** createThreejsObject */
  public function createThreejsObject($params)
    {
    $this->ModuleComponent->Main->convertToThreejs($params[1]);
    return;
    }

  /** createThumbnail */
  public function processParaviewData($params)
    {
    $this->ModuleComponent->Main->processParaviewData($params[0]);
    return;
    }

  /** can visualize?*/
  public function canVisualize($params)
    {
    return $this->ModuleComponent->Main->canVisualizeWithParaview($params['item']) || $this->ModuleComponent->Main->canVisualizeMedia($params['item']) || $this->ModuleComponent->Main->canVisualizeTxt($params['item']) || $this->ModuleComponent->Main->canVisualizeImage($params['item']) || $this->ModuleComponent->Main->canVisualizePdf($params['item']) || $this->ModuleComponent->Main->canVisualizeWebgl($params['item']);
    }

  /** generate Dasboard information */
  public function getDashboard()
    {
    $modulesConfig = Zend_Registry::get('configsModules');
    $useparaview = $modulesConfig['visualize']->useparaview;
    if(!isset($useparaview) || !$useparaview)
      {
      return false;
      }

    $server = true;

    $header = get_headers($this->getServerURL().'/PWService', 1);
    if(strpos($header[0], '404 Not Found') != false || strpos($header[0], '503 Service Temporarily Unavailable') != false)
      {
      $server = false;
      }

    $return = array();
    $return['ParaviewWeb Server'] = $server;

    return $return;
    }//end _getDasboard

  /** get server's url */
  function getServerURL()
    {
    $currentPort = "";
    $prefix = "http://";

    if($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443)
      {
      $currentPort = ":".$_SERVER['SERVER_PORT'];
      }
    if($_SERVER['SERVER_PORT'] == 443 || (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])))
      {
      $prefix = "https://";
      }
    return $prefix.$_SERVER['SERVER_NAME'].$currentPort;
    }
  } //end class
