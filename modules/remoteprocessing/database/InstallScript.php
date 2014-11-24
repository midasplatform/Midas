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

require_once BASE_PATH.'/modules/remoteprocessing/constant/module.php';

/** Install the remoteprocessing module. */
class Remoteprocessing_InstallScript extends MIDASModuleInstallScript
{
    /** @var string */
    public $moduleName = 'remoteprocessing';

    /** Post database install. */
    public function postInstall()
    {
        /** @var RandomComponent $randomComponent */
        $randomComponent = MidasLoader::loadComponent('Random');
        $securityKey = $randomComponent->generateString(32);

        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $settingModel->setConfig(MIDAS_REMOTEPROCESSING_SECURITY_KEY_KEY, $securityKey, $this->moduleName);
        $settingModel->setConfig(MIDAS_REMOTEPROCESSING_SHOW_BUTTON_KEY, MIDAS_REMOTEPROCESSING_SHOW_BUTTON_DEFAULT_VALUE, $this->moduleName);
    }
}
