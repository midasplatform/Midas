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

/** test slicerpackages notifier behavior */
class NotificationTest extends ControllerTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'packages'); // module dataset
    $this->enabledModules = array('packages');
    $this->_models = array('Folder', 'Item');

    parent::setUp();
    }

  /** Make sure that we get the slicer packages link in the left list */
  public function testLeftLinkAppears()
    {
    $this->dispatchUrI('/community');
    //TODO we need the full page layout, not just the community subview to be returned...
    //$this->assertQueryContentContains('div.SideBar a span', 'Slicer Packages');
    }

  /** Make sure that deleting an item deletes its corresponding package record */
  public function testDeleteItemDeletesPackage()
    {
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));

    $packageModel = MidasLoader::loadModel('Package', 'packages');
    $packagesFile = $this->loadData('Package', 'default', 'packages', 'packages');
    $packageDao = $packageModel->load($packagesFile[0]->getKey());
    $this->assertEquals(1, count($packageModel->getAll()));

    $itemDao = $this->Item->load($packageDao->getItemId());
    $this->assertNotEquals($itemDao, false);

    $this->Item->delete($itemDao);
    $this->assertEquals(0, count($packageModel->getAll()));
    }
  }
