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

/** API upload controller for the googleappengine module. */
class Apigoogleappengine_UploadController extends ApiController
{
    /** @var string */
    public $moduleName = 'googleappengine';

    /** Handle HTTP POST requests. */
    public function postAction()
    {
        $apiFunctions = array('callback' => 'callback', 'uploadtoken' => 'uploadToken');
        $this->_genericAction($this->_request->getParams(), $this->_request->getControllerName(),  'post',  $apiFunctions, $this->moduleName);
    }

    /** Handle HTTP OPTIONS requests. */
    public function optionsAction()
    {
        $this->_response->setHeader('Allow', 'OPTIONS, POST');
    }
}
