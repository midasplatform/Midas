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

/** ExecutableControllerTest */
class ExecutableControllerTest extends ControllerTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default', 'adminUser'));
    $this->_models = array('User', 'Item', 'ItemRevision');
    $this->enabledModules = array('remoteprocessing');
    parent::setUp();
    }

  /** test Define */
  public function testDefine()
    {
    $usersFile = $this->loadData('User', 'adminUser');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $itemFile = $this->loadData('Item', 'default');

    $revision = $this->Item->getLastRevision($itemFile[0]);

    $this->dispatchUrI('/remoteprocessing/executable/define?itemId='.$itemFile[0]->getKey(), null, true);
    $this->dispatchUrI('/remoteprocessing/executable/define?itemId='.$itemFile[0]->getKey(), $userDao, false);

    $this->assertQuery('#jsonMetadataContent');

    $this->resetAll();

    $this->params = array();
    $this->params['results'][0] = 'foo;foo;foo;foo;foo;foo';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/remoteprocessing/executable/define?itemId=".$itemFile[0]->getKey(), $userDao);

    $revisionNext = $this->Item->getLastRevision($itemFile[0]);

    // create new revision
    $this->assertNotEquals($revision->getRevision(), $revisionNext->getRevision());
    $this->ItemRevision->delete($revisionNext);
    }
  }
