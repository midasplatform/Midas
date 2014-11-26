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

/** Apifolder Component for api methods */
class Readmes_ApifolderComponent extends AppComponent
{
    /**
     * Get the readme text for a folder
     *
     * @path /readmes/folder/{id}
     * @http GET
     * @param id the id of the folder from which to get the readme
     * @return the text of the readme
     */
    public function get($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');

        /** @var Readmes_GetReadmeComponent $readmeComponent */
        $readmeComponent = MidasLoader::loadComponent('GetReadme', 'readmes');
        $apihelperComponent->validateParams($args, array('id'));

        /** @var FolderModel $folderModel */
        $folderModel = MidasLoader::loadModel('Folder');

        $folderDao = $folderModel->load($args['id']);
        $readme = $readmeComponent->fromFolder($folderDao);

        return $readme;
    }
}
