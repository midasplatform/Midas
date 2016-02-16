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

/** Index controller for the statistics module */
class Statistics_IndexController extends Statistics_AppController
{
    public $_moduleModels = array('Download');
    public $_models = array('Assetstore', 'Setting');
    public $_components = array('Utility');

    /** index action */
    public function indexAction()
    {
        $this->requireAdminPrivileges();

        $assetstores = $this->Assetstore->getAll();
        foreach ($assetstores as $key => $assetstore) {
            // Check if we can access the path
            if (file_exists($assetstore->getPath())) {
                $assetstores[$key]->totalSpace = UtilityComponent::diskTotalSpace($assetstore->getPath());
                $assetstores[$key]->freeSpace = UtilityComponent::diskFreeSpace($assetstore->getPath());
                $assetstores[$key]->usedSpace = $assetstores[$key]->totalSpace - $assetstores[$key]->freeSpace;

                if ($assetstores[$key]->totalSpace > 0) {
                    $assetstores[$key]->usedSpaceText = round(
                        ($assetstores[$key]->usedSpace / $assetstores[$key]->totalSpace) * 100,
                        2
                    );
                    $assetstores[$key]->freeSpaceText = round(
                        ($assetstores[$key]->freeSpace / $assetstores[$key]->totalSpace) * 100,
                        2
                    );
                } else {
                    $assetstores[$key]->usedSpaceText = 0;
                    $assetstores[$key]->freeSpaceText = 0;
                }
            } else {
                $assetstores[$key]->totalSpaceText = false;
            }
        }

        $jqplotAssetstoreArray = array();
        foreach ($assetstores as $assetstore) {
            $jqplotAssetstoreArray[] = array(
                $assetstore->getName().', '.$assetstore->getPath(),
                array(
                    array(
                        'Free Space: '.$this->Component->Utility->formatSize($assetstore->freeSpace),
                        $assetstore->freeSpaceText,
                    ),
                    array(
                        'Used Space: '.$this->Component->Utility->formatSize($assetstore->usedSpace),
                        $assetstore->usedSpaceText,
                    ),
                ),
            );
        }

        $this->view->json['stats']['assetstores'] = $jqplotAssetstoreArray;

        $this->view->piwikUrl = $this->Setting->getValueByName(STATISTICS_PIWIK_URL_KEY, $this->moduleName);
        $this->view->piwikId = $this->Setting->getValueByName(STATISTICS_PIWIK_SITE_ID_KEY, $this->moduleName);
        $this->view->piwikKey = $this->Setting->getValueByName(STATISTICS_PIWIK_API_KEY_KEY, $this->moduleName);
    }
}
