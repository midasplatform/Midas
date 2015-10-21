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

/** Upgrade the googleauth module to version 1.1.1. */
class Googleauth_Upgrade_1_1_1 extends MIDASUpgrade
{
    /** Post database upgrade. */
    public function postUpgrade()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $settingModel->setConfig(GOOGLE_AUTH_CLIENT_ADDITIONAL_SCOPES_KEY, GOOGLE_AUTH_CLIENT_ADDITIONAL_SCOPES_DEFAULT_VALUE, $this->moduleName);
    }
}
