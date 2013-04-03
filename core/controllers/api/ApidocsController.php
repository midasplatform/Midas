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
 * Apidocs Controller for WebApi
 **/
class Rest_ApidocsController extends AppController
{
  public $_components = array('Apidocs', 'Json');

  /** init api actions*/
  public function init()
    {
    $this->disableLayout();
    $this->disableView();
    }


  /** Index resource */
  function indexAction()
    {
    $results = array();
    $results['apiVersion'] = '1.0';
    $results['swaggerVersion'] = '1.1';
    $request = Zend_Controller_Front::getInstance()->getRequest();
    $baseUrl = $request->getScheme().'://'.$request->getHttpHost().$this->view->webroot;
    $results['basePath'] = $baseUrl. '/apidocs';
    $results['apis'] = array();

    $resources = $this->Component->Apidocs->getEnabledResources();
    foreach($resources as $resourcePath)
      {
      if(strpos($resourcePath, '/') > 0)
        {
        $resourcePath = '/' . $resourcePath;
        }
      $curResource = array();
      $curResource['path'] = $resourcePath;
      $curResource['discription'] = 'Operations about '. $resourcePath;
      array_push($results['apis'], $curResource);
      }
    echo $this->Component->Json->encode($results);
    }

  /** System resource */
  function systemAction()
    {
    $results = $this->Component->Apidocs->getResourceApiDocs('system');
    echo $this->Component->Json->encode($results);
    }

  /** Item resource */
  function itemAction()
    {
    $results = $this->Component->Apidocs->getResourceApiDocs('item');
    echo $this->Component->Json->encode($results);
    }

  /** Folder resource */
  function folderAction()
    {
    $results = $this->Component->Apidocs->getResourceApiDocs('folder');
    echo $this->Component->Json->encode($results);
    }


  /** Community resource */
  function communityAction()
    {
    $results = $this->Component->Apidocs->getResourceApiDocs('community');
    echo $this->Component->Json->encode($results);
    }


  /** Bitstream resource */
  function bitstreamAction()
    {
    $results = $this->Component->Apidocs->getResourceApiDocs('bitstream');
    echo $this->Component->Json->encode($results);
    }

  /** User resource */
  function userAction()
    {
    $results = $this->Component->Apidocs->getResourceApiDocs('user');
    echo $this->Component->Json->encode($results);
    }

  /** Group resource */
  function groupAction()
    {
    $results = $this->Component->Apidocs->getResourceApiDocs('group');
    echo $this->Component->Json->encode($results);
    }

}
