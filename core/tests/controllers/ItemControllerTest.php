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

/** test item controller */
class Core_ItemControllerTest extends ControllerTestCase
{
    /** init test */
    public function setUp()
    {
        $this->setupDatabase(array('default'));
        $this->_models = array('Item', 'ItemRevision', 'User', 'Bitstream');
        parent::setUp();
    }

    /** Test the item view */
    public function testViewAction()
    {
        $itemsFile = $this->loadData('Item', 'default');
        $usersFile = $this->loadData('User', 'default');
        $userWithPermission = $this->User->load($usersFile[0]->getKey());

        $itemDao = $this->Item->load($itemsFile[1]->getKey());
        $url = '/item/'.$itemDao->getItemId();

        // Should throw an exception for anonymous user (no read access)
        $this->dispatchUrl($url, null, true);
        $this->assertController('error');
        $this->assertAction('error');

        // Should throw an exception for no item id parameter
        $this->resetAll();
        $this->dispatchUrl('/item/view', null, true);
        $this->assertController('error');

        $this->resetAll();
        $this->dispatchUrl($url, $userWithPermission);
        $this->assertController('item');
        $this->assertAction('view');
        $this->assertQueryContentContains('a.licenseLink', 'Private License');
        $this->assertQueryContentContains('a.userTitle', 'FirstName1 LastName1');
        $this->assertQuery('table.bitstreamList');
    }

    /** Test editing item fields */
    public function testEditAction()
    {
        $itemsFile = $this->loadData('Item', 'default');
        $usersFile = $this->loadData('User', 'default');
        $userWithPermission = $this->User->load($usersFile[0]->getKey());

        $itemDao = $this->Item->load($itemsFile[3]->getKey());
        $url = '/item/edit?itemId='.$itemDao->getItemId();

        // Should throw an exception for anonymous user
        $this->dispatchUrl($url, null, true);
        $this->assertController('error');

        // Should throw an exception for no item id parameter
        $this->resetAll();
        $this->dispatchUrl('/item/edit', null, true);
        $this->assertController('error');

        // Test rendering the form
        $this->resetAll();
        $this->dispatchUrl($url, $userWithPermission);
        $this->assertController('item');
        $this->assertAction('edit');
        $this->assertQuery('form#editItemForm');
        $this->assertQuery('input[name="itemId"][value="'.$itemDao->getKey().'"]');
        $this->assertQuery('input[type="checkbox"][name="updateBitstreamName"][value="updateBitstreamName"]');
        $this->assertQuery('select[name="licenseSelect"]');
        $this->assertQuery('input[name="submit"][value="Save"]');
        $this->assertQueryContentContains('label', 'Name');
        $this->assertQueryContentContains('label', 'Description');

        // Test submitting the form
        $this->resetAll();
        $this->getRequest()->setMethod('POST');
        $this->params = array();
        $this->params['name'] = 'New name';
        $this->params['description'] = 'New description';
        $this->params['licenseSelect'] = '123';
        $this->dispatchUrl($url, $userWithPermission);
        $this->assertController('item');
        $this->assertAction('edit');
        $this->assertRedirect();

        $itemDao = $this->Item->load($itemDao->getKey());
        $lastRevision = $this->Item->getLastRevision($itemDao);
        $bitstreams = $lastRevision->getBitstreams();
        $bitstream = $bitstreams[0];
        $this->assertEquals($itemDao->getName(), 'New name');
        $this->assertEquals($itemDao->getDescription(), 'New description');
        $this->assertEquals($lastRevision->getLicenseId(), 123);
        $this->assertEquals($bitstream->getName(), 'foo_2.png');

        // Test submitting the form when "update bitstream" box is checked
        $this->resetAll();
        $this->getRequest()->setMethod('POST');
        $this->params = array();
        $this->params['name'] = 'New name';
        $this->params['description'] = 'New description';
        $this->params['licenseSelect'] = '123';
        $this->params['updateBitstreamName'] = 'updateBitstreamName';
        $this->dispatchUrl($url, $userWithPermission);
        $this->assertController('item');
        $this->assertAction('edit');
        $this->assertRedirect();

        $itemDao = $this->Item->load($itemDao->getKey());
        $lastRevision = $this->Item->getLastRevision($itemDao);
        $bitstreams = $lastRevision->getBitstreams();
        $bitstream = $bitstreams[0];
        $this->assertEquals($itemDao->getName(), 'New name');
        $this->assertEquals($itemDao->getDescription(), 'New description');
        $this->assertEquals($lastRevision->getLicenseId(), 123);
        $this->assertEquals($bitstream->getName(), 'New name');
    }

    /** Test explicit deletion of an item */
    public function testDeleteAction()
    {
        $itemsFile = $this->loadData('Item', 'default');
        $usersFile = $this->loadData('User', 'default');
        $userWithPermission = $this->User->load($usersFile[0]->getKey());
        $userWithoutPermission = $this->User->load($usersFile[1]->getKey());

        $itemDao = $this->Item->load($itemsFile[1]->getKey());
        $this->assertEquals(count($itemDao->getRevisions()), 1);
        $revisions = $itemDao->getRevisions();
        $revisionId = $revisions[0]->getItemrevisionId();

        $url = '/item/delete?itemId='.$itemDao->getItemId();

        // Should throw an exception for anonymous user
        $this->dispatchUrl($url, null, true);
        $this->assertController('error');

        // Should throw an exception for normal user
        $this->resetAll();
        $this->dispatchUrl($url, $userWithoutPermission, true);
        $this->assertController('error');

        // User with proper privileges should be able to delete the item
        $this->resetAll();
        $this->dispatchUrl($url, $userWithPermission);
        $this->assertController('item');
        $this->assertAction('delete');

        $this->assertFalse($this->Item->load($itemsFile[1]->getKey()));
        $this->assertFalse($this->ItemRevision->load($revisionId));
    }

    /** Test the editmetadata action */
    public function testEditMetadataAction()
    {
        $itemsFile = $this->loadData('Item', 'default');
        $usersFile = $this->loadData('User', 'default');
        $userWithPermission = $this->User->load($usersFile[0]->getKey());

        $itemDao = $this->Item->load($itemsFile[1]->getKey());
        $url = '/item/editmetadata?itemId='.$itemDao->getItemId().'&metadataId=1000';

        // Should throw an exception for anonymous user
        $this->dispatchUrl($url, null, true);
        $this->assertController('error');

        // Should throw an exception for no item id parameter
        $this->resetAll();
        $this->dispatchUrl('/item/editmetadata', null, true);
        $this->assertController('error');

        $this->resetAll();
        $this->dispatchUrl($url, $userWithPermission);
        $this->assertController('item');
        $this->assertAction('editmetadata');
        $this->assertQuery('form#editMetadataForm');
        $this->assertQuery('select[name="metadatatype"]');
        $this->assertQuery('input[name="element"]');
        $this->assertQuery('input[name="qualifier"]');
        $this->assertQuery('input[name="value"]');
    }

    /** Test the checkshared action */
    public function testCheckSharedAction()
    {
        $itemsFile = $this->loadData('Item', 'default');
        $usersFile = $this->loadData('User', 'default');
        $userWithPermission = $this->User->load($usersFile[0]->getKey());

        $itemDao = $this->Item->load($itemsFile[1]->getKey());
        $url = '/item/checkshared?itemId='.$itemDao->getItemId();

        $this->dispatchUrl($url, $userWithPermission);
        $resp = JsonComponent::decode($this->getBody());
        $this->assertTrue($resp == false);
    }

    /** Test deletion of an item revision */
    public function testDeleteItemRevisionAction()
    {
        $itemsFile = $this->loadData('Item', 'default');
        $usersFile = $this->loadData('User', 'default');
        $userWithPermission = $this->User->load($usersFile[0]->getKey());

        $itemDao = $this->Item->load($itemsFile[1]->getKey());
        $revisionToDelete = $this->Item->getLastRevision($itemDao);
        $this->assertTrue($revisionToDelete instanceof ItemRevisionDao);
        $url = '/item/deleteitemrevision?itemId='.$itemDao->getKey().'&itemrevisionId='.$revisionToDelete->getKey();

        // Should throw an exception for anonymous user
        $this->dispatchUrl($url, null, true);
        $this->assertController('error');

        // Should throw an exception for no item id parameter
        $this->resetAll();
        $this->dispatchUrl('/item/deleteitemrevision', null, true);
        $this->assertController('error');

        $this->dispatchUrl($url, $userWithPermission);
        $this->assertController('item');
        $this->assertAction('deleteitemrevision');
        $this->assertRedirect();
        $this->assertEquals($this->Item->getLastRevision($itemDao), null);
    }

    /** Test edit a bitstream */
    public function testEditBitstreamAction()
    {
        $bitsreamsFile = $this->loadData('Bitstream', 'default');
        $usersFile = $this->loadData('User', 'default');
        $userWithPermission = $this->User->load($usersFile[2]->getKey());

        $bitstreamToEditId = $bitsreamsFile[0]->getKey();
        $url = '/item/editbitstream?bitstreamId='.$bitstreamToEditId;

        // Should throw an exception for anonymous user
        $this->dispatchUrl($url, null, true);
        $this->assertController('error');

        // Should throw an exception for no item id parameter
        $this->resetAll();
        $this->dispatchUrl('/item/editbitstream', null, true);
        $this->assertController('error');

        // Test rendering the form
        $this->resetAll();
        $this->dispatchUrl($url, $userWithPermission);
        $this->assertController('item');
        $this->assertAction('editbitstream');
        $this->assertQuery('form#editBitstreamForm');
        $this->assertQuery('input[name="bitstreamId"][value="'.$bitstreamToEditId.'"]');
        $this->assertQuery('input[name="submit"][value="Save"]');
        $this->assertQueryContentContains('label', 'Name');
        $this->assertQueryContentContains('label', 'Mimetype');

        // Test submitting the form
        $this->resetAll();
        $this->getRequest()->setMethod('POST');
        $this->params = array();
        $this->params['name'] = 'newname.jpeg';
        $this->params['mimetype'] = 'image/jpeg';
        $this->dispatchUrl($url, $userWithPermission);
        $this->assertController('item');
        $this->assertAction('editbitstream');
        $this->assertRedirect();

        $bitstreamDao = $this->Bitstream->load($bitstreamToEditId);
        $this->assertEquals($bitstreamDao->getName(), 'newname.jpeg');
        $this->assertEquals($bitstreamDao->getMimetype(), 'image/jpeg');
    }

    /** Test delete a bitstream */
    public function testDeleteBitstreamAction()
    {
        $itemsFile = $this->loadData('Item', 'default');
        $usersFile = $this->loadData('User', 'default');
        $userWithPermission = $this->User->load($usersFile[2]->getKey());

        $itemDao = $this->Item->load($itemsFile[0]->getKey());
        $revisionDao = $this->Item->getLastRevision($itemDao);
        $old_bitstreams = $revisionDao->getBitstreams();
        $this->assertEquals(count($old_bitstreams), 1);
        $bitstreamToDeleteId = $old_bitstreams[0]->getKey();
        $url = '/item/deletebitstream?bitstreamId='.$bitstreamToDeleteId;

        // Should throw an exception for anonymous user
        $this->dispatchUrl($url, null, true);
        $this->assertController('error');

        // Should throw an exception for no item id parameter
        $this->resetAll();
        $this->dispatchUrl('/item/deletebitstream', null, true);
        $this->assertController('error');

        $this->dispatchUrl($url, $userWithPermission);
        $this->assertController('item');
        $this->assertAction('deletebitstream');
        $this->assertRedirect();
        $new_bitstreams = $revisionDao->getBitstreams();
        $this->assertEquals(count($new_bitstreams), 0);
        $bitstreamToDelete = $this->Bitstream->load($bitstreamToDeleteId);
        $this->assertFalse($bitstreamToDelete, 'Bitstream should have been deleted, but was not.');
    }
}
