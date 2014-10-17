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

/** Notification manager for the readmes module */
class Readmes_Notification extends MIDAS_Notification
{
    public $_models = array('Community', 'Folder');

    /**
     * init notification process
     */
    public function init()
    {
        $this->addCallBack('CALLBACK_CORE_FOLDER_VIEW_JS', 'getFolderViewJs');
        $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_VIEW_JSS', 'getCommunityViewJs');
    }

    /**
     * callback function to get javascript for the folder
     *
     * @return array
     */
    public function getFolderViewJs()
    {
        $fc = Zend_Controller_Front::getInstance();
        $moduleUriroot = $fc->getBaseUrl().'/modules/readmes';

        return array($moduleUriroot.'/public/js/readmes.folder.js');
    }

    /**
     * callback function to get javascript for the community
     *
     * @return array
     */
    public function getCommunityViewJs()
    {
        $fc = Zend_Controller_Front::getInstance();
        $moduleUriroot = $fc->getBaseUrl().'/modules/readmes';

        return array($moduleUriroot.'/public/js/readmes.community.js');
    }
}
