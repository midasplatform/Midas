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

require_once BASE_PATH.'/modules/dicomextractor/constant/module.php';

/** Install the dicomextractor module. */
class Dicomextractor_InstallScript extends MIDASModuleInstallScript
{
    /** @var string */
    public $moduleName = 'dicomextractor';

    /** Post database install. */
    public function postInstall()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $settingModel->setConfig(DICOMEXTRACTOR_DCM2XML_COMMAND_KEY, DICOMEXTRACTOR_DCM2XML_COMMAND_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(DICOMEXTRACTOR_DCMJ2PNM_COMMAND_KEY, DICOMEXTRACTOR_DCMJ2PNM_COMMAND_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(DICOMEXTRACTOR_DCMFTEST_COMMAND_KEY, DICOMEXTRACTOR_DCMFTEST_COMMAND_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(DICOMEXTRACTOR_DCMDICTPATH_KEY, DICOMEXTRACTOR_DCMDICTPATH_DEFAULT_VALUE, $this->moduleName);
    }
}
