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

/** Apicommunity Component for api methods */
class Readmes_ApicommunityComponent extends AppComponent
{
    /**
     * Get the readme text for a community
     *
     * @path /readmes/community/{id}
     * @http GET
     * @param id the id of the community from which to get the readme
     * @return the text of the readme
     */
    public function get($args)
    {
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $readmeComponent = MidasLoader::loadComponent('GetReadme', 'readmes');
        $apihelperComponent->validateParams($args, array('id'));

        $communityModel = MidasLoader::loadModel('Community');

        $communityDao = $communityModel->load($args['id']);
        $readme = $readmeComponent->fromCommunity($communityDao);

        return $readme;
    }
}
