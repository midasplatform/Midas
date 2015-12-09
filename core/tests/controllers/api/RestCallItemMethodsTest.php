<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

require_once BASE_PATH.'/core/tests/controllers/api/RestCallMethodsTestCase.php';

/** Tests the functionality of the web API Rest Item methods. */
class Core_RestCallItemMethodsTest extends RestCallMethodsTestCase
{
    /** Set up tests. */
    public function setUp()
    {
        parent::setUp();
    }

    /** Set metadata on the item. */
    public function testItemAddmetadata()
    {
        $itemsFile = $this->loadData('Item', 'default');
        $itemDao = $this->Item->load($itemsFile[1]->getKey());

        $apiPath = '/item/addmetadata/'.$itemDao->getItemId();

        // No user will fail.
        $this->resetAll();
        $resp = $this->_callRestApi('PUT', $apiPath);
        $this->_assertStatusFail($resp);

        $usersFile = $this->loadData('User', 'default');
        $userDao = $this->User->load($usersFile[0]->getKey());

        // Lack of request body will fail.
        $this->resetAll();
        $this->params['useSession'] = 'true';
        $resp = $this->_callRestApi('PUT', $apiPath, $userDao);
        $this->_assertStatusFail($resp);

        // Request body without a 'metadata' key will fail.
        $this->resetAll();
        $this->params['useSession'] = 'true';
        $this->params[0] = json_encode(array('murkydata' => array()));
        $resp = $this->_callRestApi('PUT', $apiPath, $userDao);
        $this->_assertStatusFail($resp);

        // Metadatum needs 'value' key.
        $this->resetAll();
        $this->params['useSession'] = 'true';
        $this->params[0] = json_encode(array('metadata' => array('element' => 'key1')));
        $resp = $this->_callRestApi('PUT', $apiPath, $userDao);
        $this->_assertStatusFail($resp);

        // Metadatum needs 'element' key.
        $this->resetAll();
        $this->params['useSession'] = 'true';
        $this->params[0] = json_encode(array('metadata' => array('value' => 'val1')));
        $resp = $this->_callRestApi('PUT', $apiPath, $userDao);
        $this->_assertStatusFail($resp);

        // Write some metadata correctly.
        $this->resetAll();
        $this->params['useSession'] = 'true';
        $this->params[0] = json_encode(array('metadata' => array(
            array('element' => 'key1', 'value' => 'val1'),
            array('element' => 'key2', 'value' => 'val2'),
        )));
        $resp = $this->_callRestApi('PUT', $apiPath, $userDao);
        $this->_assertStatusOk($resp);

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $revisionDao = $itemModel->getLastRevision($itemDao);
        /** @var ItemRevisionModel $itemRevisionModel */
        $itemRevisionModel = MidasLoader::loadModel('ItemRevision');
        $metadata = $itemRevisionModel->getMetadata($revisionDao);
        $metadataArray = array();
        $key1 = false;
        $key2 = false;
        foreach ($metadata as $metadatum) {
            if ($metadatum->element === 'key1' && $metadatum->value === 'val1') {
                $key1 = true;
            }
            if ($metadatum->element === 'key2' && $metadatum->value === 'val2') {
                $key2 = true;
            }
        }
        $this->assertTrue($key1 && $key2, 'Metadata incorrectly set');

        // Update key1, add key3, leave key2 alone.
        $this->resetAll();
        $this->params['useSession'] = 'true';
        $this->params[0] = json_encode(array('metadata' => array(
            array('element' => 'key1', 'value' => 'newval1'),
            array('element' => 'key3', 'value' => 'val3'),
        )));
        $resp = $this->_callRestApi('PUT', $apiPath, $userDao);
        $this->_assertStatusOk($resp);

        $metadata = $itemRevisionModel->getMetadata($revisionDao);
        $metadataArray = array();
        $key1 = false;
        $key2 = false;
        $key3 = false;
        foreach ($metadata as $metadatum) {
            if ($metadatum->element === 'key1' && $metadatum->value === 'newval1') {
                $key1 = true;
            }
            if ($metadatum->element === 'key2' && $metadatum->value === 'val2') {
                $key2 = true;
            }
            if ($metadatum->element === 'key3' && $metadatum->value === 'val3') {
                $key3 = true;
            }
        }
        $this->assertTrue($key1 && $key2 && $key3, 'Metadata incorrectly set');
    }
}
