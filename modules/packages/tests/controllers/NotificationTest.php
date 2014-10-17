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

/** test slicerpackages notifier behavior */
class NotificationTest extends ControllerTestCase
{
    /** set up tests */
    public function setUp()
    {
        $this->setupDatabase(array('default')); // core dataset
        $this->setupDatabase(array('default'), 'packages'); // module dataset
        $this->enabledModules = array('packages');
        $this->_models = array('Folder', 'Item');

        parent::setUp();
    }

    /** Make sure that we get the slicer packages link in the left list */
    public function testLeftLinkAppears()
    {
        $this->dispatchUrI('/community');
        // TODO we need the full page layout, not just the community subview to be returned...
        // $this->assertQueryContentContains('div.SideBar a span', 'Slicer Packages');
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
