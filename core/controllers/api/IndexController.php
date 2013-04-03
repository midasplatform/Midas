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
 * Index Controller for WebApi
 **/
class Rest_IndexController extends AppController
{
  public function preDispatch()
    {
    parent::preDispatch();
    $this->view->setScriptPath(BASE_PATH."/core/views/rest");
    }


  /** Index function */
  function indexAction()
    {
    $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->coreWebroot.'/public/images/icons/page_white_code_red.png" />';
    $header .= ' REST Web API';
    $this->view->header = $header;
    $this->view->serverURL = $this->getServerURL();
    }
}
