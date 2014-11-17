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

/** Notification manager for the visualize module */
class Visualize_Notification extends MIDAS_Notification
{
    public $_models = array('Setting');
    public $_moduleComponents = array('Main');
    public $moduleName = 'visualize';

    /** init notification process */
    public function init()
    {
        $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDashboard');
        $this->addCallBack('CALLBACK_VISUALIZE_CAN_VISUALIZE', 'canVisualize');
        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_ACTIONMENU', 'getItemViewLink');
    }

    /** If this object is able to be slice viewed, we show a link for that */
    public function getItemViewLink($params)
    {
        $item = $params['item'];
        if ($this->ModuleComponent->Main->canVisualizeWithSliceView($item)) {
            $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
            $html = '<li><a href="'.$webroot.'/'.$this->moduleName.'/paraview/slice?itemId=';
            $html .= $item->getKey().'"><img alt="" src="'.$webroot.'/modules/';
            $html .= $this->moduleName.'/public/images/sliceView.png" /> Slice Visualization</a></li>';

            $html .= '<li><a href="'.$webroot.'/'.$this->moduleName.'/paraview/volume?itemId=';
            $html .= $item->getKey().'"><img alt="" src="'.$webroot.'/modules/';
            $html .= $this->moduleName.'/public/images/volume.png" /> Volume Visualization</a></li>';

            return $html;
        } elseif ($this->ModuleComponent->Main->canVisualizeWithSurfaceView($item)
        ) {
            $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
            $html = '<li><a href="'.$webroot.'/'.$this->moduleName.'/paraview/surface?itemId=';
            $html .= $item->getKey().'"><img alt="" src="'.$webroot.'/modules/';
            $html .= $this->moduleName.'/public/images/pqUnstructuredGrid16.png" /> Surface Visualization</a></li>';

            return $html;
        }
    }

    /** can visualize?*/
    public function canVisualize($params)
    {
        return $this->ModuleComponent->Main->canVisualizeWithParaview(
            $params['item']
        ) || $this->ModuleComponent->Main->canVisualizeMedia(
            $params['item']
        ) || $this->ModuleComponent->Main->canVisualizeTxt(
            $params['item']
        ) || $this->ModuleComponent->Main->canVisualizeImage(
            $params['item']
        ) || $this->ModuleComponent->Main->canVisualizePdf(
            $params['item']
        ) || $this->ModuleComponent->Main->canVisualizeWebgl($params['item']);
    }

    /** generate dashboard information */
    public function getDashboard()
    {
        $useParaView = $this->Setting->getValueByName(VISUALIZE_USE_PARAVIEW_WEB_KEY, $this->moduleName);

        if (!isset($useParaView) || !$useParaView) {
            return false;
        }

        $header = get_headers($this->getServerURL().'/PWService', 1);

        if (strpos($header[0], '404 Not Found') !== false || strpos(
                $header[0],
                '503 Service Temporarily Unavailable'
            ) !== false
        ) {
            return array('ParaViewWeb Server' => array(false, 'Could not connect to ParaViewWeb server'));
        }

        return array('ParaViewWeb Server' => array(true, ''));
    }

    /** get server's url */
    public function getServerURL()
    {
        $currentPort = "";
        $prefix = "http://";

        if (isset($_SERVER["SERVER_PORT"]) && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
            $currentPort = ":".$_SERVER['SERVER_PORT'];
        }
        if ((isset($_SERVER["SERVER_PORT"]) && $_SERVER['SERVER_PORT'] == 443) || (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']))) {
            $prefix = "https://";
        }

        return $prefix.$_SERVER['SERVER_NAME'].$currentPort;
    }
}
