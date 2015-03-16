<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

require_once BASE_PATH.'/modules/api/library/APIEnabledNotification.php';

/** Notification manager for the dicomserver module */
class Dicomserver_Notification extends ApiEnabled_Notification
{
    public $_moduleComponents = array('Api', 'Server');
    public $moduleName = 'dicomserver';

    /** init notification process */
    public function init()
    {
        $this->enableWebAPI($this->moduleName);
        $fc = Zend_Controller_Front::getInstance();
        $this->moduleWebroot = $fc->getBaseUrl().'/modules/'.$this->moduleName;
        $this->coreWebroot = $fc->getBaseUrl().'/core';
        $this->apiWebroot = $fc->getBaseURL().'/modules/api';

        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_ACTIONMENU', 'getItemMenuLink');
        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_JS', 'getJs');
        $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDashboard');
        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_INFO', 'getItemInfo');
    }

    /** Get the link to place in the item action menu */
    public function getItemMenuLink($params)
    {
        $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
        $html = '<li id="dicomRegisterListItem" style="display: none;">';
        $html .= '<a id="dicomRegisterAction" href="#">';
        $html .= '<img alt="" src="'.$webroot.'/modules/';
        $html .= $this->moduleName.'/public/images/dicom_register_icon.jpg" /> ';
        $html .= $this->t('Register for DICOM Query/Retrieve').'</a></li>';

        return $html;
    }

    /**
     * Get javascript for the item view that will specify the ajax call
     * for DICOM registration
     */
    public function getJs($params)
    {
        return array(
            $this->moduleWebroot.'/public/js/item/dicomserver.item.view.js',
            $this->apiWebroot.'/public/js/common/common.ajaxapi.js',
        );
    }

    /** Add admin dashboard entry for DICOM server */
    public function getDashboard()
    {
        $return = $this->ModuleComponent->Server->isDICOMServerWorking();

        return $return;
    }

    /** Some html to be appended to the item view sidebar */
    public function getItemInfo($params)
    {
        return '<div class="sideElement" id="sideElementDicomRegistration" style="display: none;">
              <h1>DICOM</h1>
              <span>This item was registered for DICOM Query/Retrieve services.</span><br/>
              <span>Note: if the latest revision is updated, registration action needs to be rerun.</span>
            </div>';
    }
}
