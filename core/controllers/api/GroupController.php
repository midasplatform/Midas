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

/**
 *  Web API controller for the Group resource
 */
class Rest_GroupController extends ApiController
  {
  /**
   * The index action handles index/list requests; it should respond with a
   * list of the requested resources.
   */
  public function indexAction()
    {
    $this->forward('get');
    }

  /**
   * The head action handles HEAD requests; it should respond with an
   * identical response to the one that would correspond to a GET request,
   * but without the response body.
   */
  public function headAction()
    {
    $this->_response->setHttpResponseCode(200); // 200 OK
    }

  /**
   * The get action handles GET requests and receives an 'id' parameter; it
   * should respond with the server resource state of the resource identified
   * by the 'id' value.
   */
  public function getAction()
    {
    $apiFunctions['default'] = 'groupGet';
    $apiFunctions['users'] = 'groupListUsers';
    $this->_genericAction($this->_request->getParams(), $this->_request->getControllerName(), 'get', $apiFunctions);
    }

 /**
   * The put action handles PUT requests and receives an 'id' parameter; it
   * should update the server resource state of the resource identified by
   * the 'id' value.
   */
  public function putAction()
    {
    $apiFunctions = array(
      'default' => 'groupAddUser',
      'adduser'=> 'groupAddUser',
      'removeuser' => 'groupRemoveUser',
      );
    $this->_genericAction($this->_request->getParams(), $this->_request->getControllerName(), 'put', $apiFunctions);
    }

   /**
   * The post action handles POST requests; it should accept and digest a
   * POSTed resource representation and persist the resource state.
   */
  public function postAction()
    {
    $apiFunctions['default'] = 'groupAdd';
    $this->_genericAction($this->_request->getParams(), $this->_request->getControllerName(), 'post', $apiFunctions);
    }

  /**
   * The delete action handles DELETE requests and receives an 'id'
   * parameter; it should update the server resource state of the resource
   * identified by the 'id' value.
   */
  public function deleteAction()
    {
    $apiFunctions['default'] = 'groupRemove';
    $this->_genericAction($this->_request->getParams(), $this->_request->getControllerName(), 'delete', $apiFunctions);
    }

  /**
   * The options action handles OPTIONS requests; it should respond with
   * the HTTP methods that the server supports for specified URL.
   */
  public function optionsAction()
    {
    $this->_response->setHeader('Allow', 'OPTIONS, HEAD, GET, PUT, POST, DELETE');
    }
  }
