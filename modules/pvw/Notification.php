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

/** Notification manager for the pvw module */
class Pvw_Notification extends MIDAS_Notification
{
    public $_moduleComponents = array('Validation');
    public $moduleName = 'pvw';

    /** init notification process */
    public function init()
    {
        $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDashboard');
        $this->addCallBack('CALLBACK_CORE_ADMIN_TABS', 'getAdminTab');
        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_ACTIONMENU', 'getItemViewLink');
    }

    /** If this object is able to be slice viewed, we show a link for that */
    public function getItemViewLink($params)
    {
        $item = $params['item'];
        $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
        if ($this->ModuleComponent->Validation->canVisualizeWithSliceView($item)
        ) {
            $html = '<li><a href="'.$webroot.'/'.$this->moduleName.'/paraview/slice?itemId='.$item->getKey().'">';
            $html .= '<img alt="" src="'.$webroot.'/modules/'.$this->moduleName.'/public/images/sliceView.png" /> ';
            $html .= 'Slice Visualization</a></li>';

            $html .= '<li><a href="'.$webroot.'/'.$this->moduleName.'/paraview/volume?itemId='.$item->getKey().'">';
            $html .= '<img alt="" src="'.$webroot.'/modules/'.$this->moduleName.'/public/images/volume.png" /> ';
            $html .= 'Volume Visualization</a></li>';

            return $html;
        } elseif ($this->ModuleComponent->Validation->canVisualizeWithSurfaceView($item)
        ) {
            $html = '<li><a href="'.$webroot.'/'.$this->moduleName.'/paraview/surface?itemId='.$item->getKey().'">';
            $html .= '<img alt="" src="'.$webroot.'/modules/'.$this->moduleName.'/public/images/pqUnstructuredGrid16.png" /> ';
            $html .= 'Surface Visualization</a></li>';

            return $html;
        }
    }

    /** generate Dashboard information */
    public function getDashboard()
    {
        $settingModel = MidasLoader::loadModel('Setting');
        $pvpython = $settingModel->getValueByName(MIDAS_PVW_PVPYTHON_KEY, $this->moduleName);

        // Validate pvpython setting
        if (!$pvpython) {
            $pvpDb = array(false, 'Must set pvpython path in module config page');
        } else {
            $pvpDb = array(is_executable($pvpython), $pvpython);
        }

        // Validate static content directory setting
        $staticDir = BASE_PATH.'/modules/pvw/public/import';
        $staticDb = array(is_dir($staticDir), $staticDir);

        return array('pvpython is executable' => $pvpDb, 'Static content directory' => $staticDb);
    }

    /**
     * Return the ParaViewWeb admin tab link
     */
    public function getAdminTab()
    {
        $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();

        return array('ParaViewWeb' => $webroot.'/'.$this->moduleName.'/admin/status');
    }
}
