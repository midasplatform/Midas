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

/** Test the extract archive functionality */
class ArchiveExtractControllerTest extends ControllerTestCase
{
    /** set up tests */
    public function setUp()
    {
        $this->setupDatabase(array('default')); // core dataset
        $this->enabledModules = array('archive');
        $this->moduleName = 'archive';
        $this->_models = array('Bitstream', 'Folder', 'Item', 'User');
        $this->_daos = array();

        parent::setUp();
    }

    /** Render the dialog for coverage */
    public function testDialogAction()
    {
        $this->dispatchUrI('/archive/extract/dialog');
    }

    /** Test extraction of a zip file */
    public function testPerformAction()
    {
        // Simulate uploading our zip file into the assetstore (cp instead of mv)
        $uploadComponent = MidasLoader::loadComponent('Upload');

        $adminUser = $this->User->load(3);
        $adminFolders = $adminUser->getFolder()->getFolders();
        $parent = $adminFolders[0];
        $path = BASE_PATH.'/modules/archive/tests/data/test.zip';
        $item = $uploadComponent->createUploadedItem($adminUser, 'test.zip', $path, $parent, null, '', true);

        // Should fail when we try without privileges
        $url = '/archive/extract/perform?deleteArchive=true&itemId='.$item->getKey();
        $this->dispatchUrI($url, null, true);

        // Now run with proper privileges
        $this->resetAll();
        $this->dispatchUrI($url, $adminUser);

        // The original item should have been deleted
        $item = $this->Item->load($item->getKey());
        $this->assertFalse($item);

        // It should be replaced by the expected hierarchy
        $sortDaoComponent = MidasLoader::loadComponent('Sortdao');
        $sortDaoComponent->field = 'name';

        $childItems = $parent->getItems();
        $this->assertEquals(count($childItems), 2);
        usort($childItems, array($sortDaoComponent, 'sortByName'));

        $child0 = $childItems[0];
        $this->assertEquals($child0->getName(), 'AppController.php');

        $child1 = $childItems[1];
        $this->assertEquals($child1->getName(), 'Notification.php');

        $childFolders = $parent->getFolders();
        $this->assertEquals(count($childFolders), 2);
        usort($childFolders, array($sortDaoComponent, 'sortByName'));

        $childPublic = $childFolders[0];
        $this->assertEquals($childPublic->getName(), 'public');

        $childTranslation = $childFolders[1];
        $this->assertEquals($childTranslation->getName(), 'translation');

        $translationChildFolders = $childTranslation->getFolders();
        $this->assertEquals(count($translationChildFolders), 0);

        $translationChildItems = $childTranslation->getItems();
        $this->assertEquals(count($translationChildItems), 1);
        $this->assertEquals($translationChildItems[0]->getName(), 'fr-main.csv');

        $publicChildItems = $childPublic->getItems();
        $this->assertEquals(count($publicChildItems), 0);

        $publicChildFolders = $childPublic->getFolders();
        $this->assertEquals(count($publicChildFolders), 2);
        usort($publicChildFolders, array($sortDaoComponent, 'sortByName'));

        $this->assertEquals($publicChildFolders[0]->getName(), 'css');
        $this->assertEquals($publicChildFolders[1]->getName(), 'js');
        // The rest is assumed to follow by induction :)
    }
}
