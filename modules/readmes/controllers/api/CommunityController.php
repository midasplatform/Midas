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

/** Web API controller for the Community resource (in readmes module). */
class Apireadmes_CommunityController extends ApiController
{
    /**
     * The put action handles GET requests and receives an 'id' parameter; it
     * should update the server resource state of the resource identified by
     * the 'id' value.
     */
    public function getAction()
    {
        $apiFunctions['default'] = 'get';
        $this->_genericAction(
            $this->_request->getParams(),
            $this->_request->getControllerName(),
            'get',
            $apiFunctions,
            'readmes'
        );
    }

    /**
     * The options action handles OPTIONS requests; it should respond with
     * the HTTP methods that the server supports for specified URL.
     */
    public function optionsAction()
    {
        $this->_response->setHeader('Allow', 'OPTIONS, GET');
    }
}
