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

/** API producer controller for the tracker module. */
class Apitracker_ProducerController extends ApiController
{
    /** @var string */
    public $moduleName = 'tracker';

    /** Handle HTTP DELETE requests. Requires an id parameter. */
    public function deleteAction()
    {
        $this->_genericAction(
            $this->_request->getParams(),
            $this->_request->getControllerName(),
            $this->_request->getActionName(),
            array('default' => $this->_request->getActionName()),
            $this->moduleName,
            false
        );
    }

    /** Handle HTTP GET requests. Requires an id parameter. */
    public function getAction()
    {
        $this->_genericAction(
            $this->_request->getParams(),
            $this->_request->getControllerName(),
            $this->_request->getActionName(),
            array('default' => $this->_request->getActionName()),
            $this->moduleName,
            false
        );
    }

    /** Handle HTTP HEAD requests. */
    public function headAction()
    {
        $this->_response->setHttpResponseCode(200); // 200 OK
    }

    /** Handle HTTP GET index or list requests. */
    public function indexAction()
    {
        $this->_genericAction(
            $this->_request->getParams(),
            $this->_request->getControllerName(),
            $this->_request->getActionName(),
            array('default' => $this->_request->getActionName()),
            $this->moduleName,
            false
        );
    }

    /** Handle HTTP OPTIONS requests. */
    public function optionsAction()
    {
        $this->_response->setHeader('Allow', 'DELETE, GET, HEAD, OPTIONS, POST, PUT');
    }

    /** Handle HTTP POST requests. */
    public function postAction()
    {
        $this->_genericAction(
            $this->_request->getParams(),
            $this->_request->getControllerName(),
            $this->_request->getActionName(),
            array('default' => $this->_request->getActionName()),
            $this->moduleName,
            false
        );
    }

    /** Handle HTTP PUT requests. Requires an id parameter. */
    public function putAction()
    {
        $this->_genericAction(
            $this->_request->getParams(),
            $this->_request->getControllerName(),
            $this->_request->getActionName(),
            array('default' => $this->_request->getActionName()),
            $this->moduleName,
            false
        );
    }
}
