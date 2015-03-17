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

require_once BASE_PATH.'/modules/visualize/constant/module.php';

/** Install the visualize module. */
class Visualize_InstallScript extends MIDASModuleInstallScript
{
    /** @var string */
    public $moduleName = 'visualize';

    /** Post database install. */
    public function postInstall()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $settingModel->setConfig(VISUALIZE_TEMPORARY_DIRECTORY_KEY, VISUALIZE_TEMPORARY_DIRECTORY_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(VISUALIZE_USE_PARAVIEW_WEB_KEY, VISUALIZE_USE_PARAVIEW_WEB_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(VISUALIZE_USE_WEB_GL_KEY, VISUALIZE_USE_WEB_GL_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(VISUALIZE_USE_SYMLINKS_KEY, VISUALIZE_USE_SYMLINKS_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(VISUALIZE_TOMCAT_ROOT_URL_KEY, VISUALIZE_TOMCAT_ROOT_URL_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(VISUALIZE_PVBATCH_COMMAND_KEY, VISUALIZE_PVBATCH_COMMAND_DEFAULT_VALUE, $this->moduleName);
        $settingModel->setConfig(VISUALIZE_PARAVIEW_WEB_WORK_DIRECTORY_KEY, VISUALIZE_PARAVIEW_WEB_WORK_DIRECTORY_DEFAULT_VALUE, $this->moduleName);
    }
}
