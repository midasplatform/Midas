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


/** index controller test*/
class IndexControllerTest extends ControllerTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->enabledModules = array('visualize');
    parent::setUp();
    }

  /** test index action*/
  public function testIndexAction()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $itemModel = $modelLoad->loadModel('Item');
    $groupModel = $modelLoad->loadModel('Group');
    $itempolicygroupModel = $modelLoad->loadModel('Itempolicygroup');
    $userModel = $modelLoad->loadModel('User');

    $componentLoader = new MIDAS_ComponentLoader();
    $uploadComponent = $componentLoader->loadComponent('Upload');

    $usersFile = $this->loadData('User', 'default');
    $userDao = $userModel->load($usersFile[0]->getKey());

    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
    Zend_Registry::set('configsModules', array());
    $item = $uploadComponent->createUploadedItem($userDao, "test.png", BASE_PATH.'/tests/testfiles/search.png', null, null, '', true);
    $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
    $itempolicygroupModel->createPolicy($anonymousGroup, $item, MIDAS_POLICY_READ);
    $this->params['itemId'] = $item->getKey();
    $this->dispatchUrI("/visualize/index/index");
    $this->assertController("index");

    $this->dispatchUrI("/visualize/image/index");
    $this->assertController("image");

    $this->dispatchUrI("/visualize/media/index");
    $this->assertController("media");

    $this->dispatchUrI("/visualize/pdf/index");
    $this->assertController("pdf");

    $this->dispatchUrI("/visualize/txt/index");
    $this->assertController("txt");

    $this->dispatchUrI("/visualize/webgl/index");
    $this->assertController("webgl");
    }
  }