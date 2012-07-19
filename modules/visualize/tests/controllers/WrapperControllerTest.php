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

// need to include the module constant for this test
require_once str_replace('tests', 'constant', str_replace('controllers', 'module.php', dirname(__FILE__)));

/** config controller test*/
class WrappergControllerTest extends ControllerTestCase
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
    $groupModel = MidasLoader::loadModel('Group');
    $itempolicygroupModel = MidasLoader::loadModel('Itempolicygroup');
    $userModel = MidasLoader::loadModel('User');

    $uploadComponent = MidasLoader::loadComponent('Upload');

    $usersFile = $this->loadData('User', 'default');
    $userDao = $userModel->load($usersFile[0]->getKey());

    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
    Zend_Registry::set('configsModules', array());
    $item = $uploadComponent->createUploadedItem($userDao, "test.png", BASE_PATH.'/tests/testfiles/search.png', null, null, '', true);
    $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
    $itempolicygroupModel->createPolicy($anonymousGroup, $item, MIDAS_POLICY_READ);

    $this->params['itemId'] = $item->getKey();
    $this->dispatchUrI("/visualize/wrapper");

    $this->assertAction("index");
    $this->assertModule("visualize");
    }
  }