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

/** Notification manager for the archive module */
class Archive_Notification extends MIDAS_Notification
{
    public $moduleName = 'archive';

    /** init notification process */
    public function init()
    {
        $fc = Zend_Controller_Front::getInstance();
        $this->webroot = $fc->getBaseUrl();
        $this->moduleWebroot = $this->webroot.'/modules/'.$this->moduleName;
        $this->coreWebroot = $this->webroot.'/core';

        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_ACTIONMENU', 'getItemAction');
        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_JS', 'getItemViewJs');
    }

    /** Get item action for extracting zip */
    public function getItemAction($params)
    {
        $item = $params['item'];
        $isAdmin = $params['isAdmin'];

        if (!$isAdmin) {
            return null;
        }

        if (substr(strtolower($item->getName()), -4) === '.zip') {
            return '<li><a class="archiveExtractAction" href="javascript:;">'.'<img alt="" src="'.$this->coreWebroot.'/public/images/icons/page_white_zip.png" /> '.'Extract archive</a></li>';
        } else {
            return null;
        }
    }

    /** Get item view js */
    public function getItemViewJs($params)
    {
        return array($this->moduleWebroot.'/public/js/common/archive.common.js');
    }
}
