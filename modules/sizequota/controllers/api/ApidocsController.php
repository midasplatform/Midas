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
 * Apidocs Controller for Sizequota WebApi
 **/
class Apisizequota_ApidocsController extends AppController
{
  public $_components = array('Apidocs', 'Json');


  /** init api actions*/
  public function init()
    {
    $this->disableLayout();
    $this->disableView();
    }

  /** User function */
  function userAction()
    {
    $results = $this->Component->Apidocs->getModelApiDocs('user', 'sizequota');
    echo $this->Component->Json->encode($results);
    }

}
