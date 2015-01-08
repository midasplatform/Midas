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

/** Thing controller test for the @MN@ module. */
class @MN_CAP@_ThingControllerTest extends ControllerTestCase
{
    /** @TODO Setup the tests. */
    public function setUp()
    {
        $this->enabledModules = array('@MN@');

        parent::setUp();
    }

    /** @TODO Test the get action. */
    public function testGetAction()
    {
        $params = array('action' => 'get', 'controller' => 'Thing', 'module' => '@MN@');
        $urlParams = $this->urlizeOptions($params);
        $id = 1;
        $url = $this->url($urlParams, '@MN@-1').'?id='.$id;
        $this->dispatch($url);

        $this->assertModule($urlParams['module']);
        $this->assertController($urlParams['controller']);
        $this->assertAction($urlParams['action']);
        $this->assertQueryContentContains('div.viewMain p', 'The id parameter passed in is: '.$id.'.');
    }
}
