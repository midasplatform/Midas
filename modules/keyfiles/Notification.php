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

/** Notification manager for the keyfiles module */
class Keyfiles_Notification extends MIDAS_Notification
{
    public $moduleName = 'keyfiles';

    /** Register callbacks */
    public function init()
    {
        $fc = Zend_Controller_Front::getInstance();
        $this->moduleWebroot = $fc->getBaseUrl().'/modules/'.$this->moduleName;
        $this->coreWebroot = $fc->getBaseUrl().'/core';

        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_ACTIONMENU', 'getItemMenuLink');
        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_JS', 'getItemViewJs');
        $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_CSS', 'getItemViewCss');
        $this->addCallBack('CALLBACK_CORE_GET_FOOTER_LAYOUT', 'getFooter');
    }

    /** Get the link to place in the item action menu */
    public function getItemMenuLink($params)
    {
        $item = $params['item'];
        $revisions = $item->getRevisions();
        if (count($revisions) === 0) {
            return null;
        }
        $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();

        return '<li><a href="'.$webroot.'/'.$this->moduleName.'/download/item?itemId='.htmlspecialchars($params['item']->getKey(), ENT_QUOTES, 'UTF-8').
        '"><img alt="" src="'.$webroot.'/core/public/images/icons/key.png" /> Download key files</a></li>';
    }

    /** Get javascript for the item view */
    public function getItemViewJs($params)
    {
        return array($this->moduleWebroot.'/public/js/item/keyfiles.item.view.js');
    }

    /** Get stylesheets for the item view */
    public function getItemViewCss($params)
    {
        return array($this->moduleWebroot.'/public/css/item/keyfiles.item.view.css');
    }

    /** Get js to append to footer */
    public function getFooter()
    {
        return '<script type="text/javascript" src="'.$this->moduleWebroot.'/public/js/'.$this->moduleName.'.notify.js"></script>';
    }
}
