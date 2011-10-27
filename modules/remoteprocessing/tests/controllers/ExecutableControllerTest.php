<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
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
