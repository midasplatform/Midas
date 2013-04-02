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


  /** Index function */
  function indexAction()
    {
    $results = array();
    $results['apiVersion'] = '1.0';
    $results['swaggerVersion'] = '1.1';
    $request = Zend_Controller_Front::getInstance()->getRequest();
    $baseUrl = $request->getScheme().'://'.$request->getHttpHost().$this->view->webroot;
    $results['basePath'] = $baseUrl. '/apidocs';
    $results['apis'] = array();

    $apiDocs = $this->Component->Apidocs->getWebApiDocs();
    $models = array_keys($apiDocs);
    foreach($models as $modelPath)
      {
      if(strpos($modelPath, '/') > 0)
        {
        $modelPath = '/' . $modelPath;
        }
      $curModel = array();
      $curModel['path'] = $modelPath;
      $curModel['discription'] = 'Operations about '. $modelPath;
      array_push($results['apis'], $curModel);
      }
    echo $this->Component->Json->encode($results);
    }

  /** Item function */
  function itemAction()
    {
    $results = $this->Component->Apidocs->getModelApiDocs('item');
    echo $this->Component->Json->encode($results);
    }

  /** Folder function */
  function folderAction()
    {
    $results = $this->Component->Apidocs->getModelApiDocs('folder');
    echo $this->Component->Json->encode($results);
    }


  /** Community function */
  function communityAction()
    {
    $results = $this->Component->Apidocs->getModelApiDocs('community');
    echo $this->Component->Json->encode($results);
    }


  /** Bitstream function */
  function bitstreamAction()
    {
    $results = $this->Component->Apidocs->getModelApiDocs('bitstream');
    echo $this->Component->Json->encode($results);
    }

  /** User function */
  function userAction()
    {
    $results = $this->Component->Apidocs->getModelApiDocs('user');
    echo $this->Component->Json->encode($results);
    }

}
