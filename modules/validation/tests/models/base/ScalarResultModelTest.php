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

/** test ScalarResultModel */
class Validation_ScalarResultModelTest extends DatabaseTestCase
{
    /** set up tests */
    public function setUp()
    {
        $db = Zend_Registry::get('dbAdapter');
        $configDatabase = Zend_Registry::get('configDatabase');
        if ($configDatabase->database->adapter == 'PDO_PGSQL') {
            $db->query(
                "SELECT setval('validation_dashboard_dashboard_id_seq', (SELECT MAX(dashboard_id) FROM validation_dashboard)+1);"
            );
            $db->query(
                "SELECT setval('validation_dashboard2folder_dashboard2folder_id_seq', (SELECT MAX(dashboard2folder_id) FROM validation_dashboard2folder)+1);"
            );
            $db->query(
                "SELECT setval('validation_dashboard2scalarresult_dashboard2scalarresult_id_seq', (SELECT MAX(dashboard2scalarresult_id) FROM  validation_dashboard2scalarresult)+1);"
            );
            $db->query(
                "SELECT setval('validation_scalarresult_scalarresult_id_seq', (SELECT MAX(scalarresult_id) FROM validation_scalarresult)+1);"
            );
        }
        $this->setupDatabase(array('default')); // core dataset
        $this->setupDatabase(array('default'), 'validation'); // module dataset
        $this->enabledModules = array('validation');
        $this->_models = array('Folder', 'Item');
        $this->_daos = array('Folder', 'Item');
        parent::setUp();
    }

    /** testGetAll */
    public function testGetAll()
    {
        /** @var Validation_ScalarResultModel $scalarResultModel */
        $scalarResultModel = MidasLoader::loadModel('ScalarResult', 'validation');
        $daos = $scalarResultModel->getAll();
        $this->assertEquals(1, count($daos));
    }

    /** testGetSetValue */
    public function testGetSetValue()
    {
        Zend_Registry::set('modulesEnable', $this->enabledModules);
        Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));

        /** @var Validation_ScalarResultModel $scalarResultModel */
        $scalarResultModel = MidasLoader::loadModel('ScalarResult', 'validation');
        $daos = $scalarResultModel->getAll();
        $sr = $daos[0];

        $folder = new FolderDao();
        $folder->setName('result');
        $folder->setDescription('result');
        $folder->setParentId(-1);
        $this->Folder->save($folder);

        $trainingItem = new ItemDao();
        $trainingItem->setName('img00.mha');
        $trainingItem->setDescription('training img 00');
        $trainingItem->setType(0);
        $this->Item->save($trainingItem);

        $scalarResultModel->setFolder($sr, $folder);
        $scalarResultModel->setItem($sr, $trainingItem);
        $sr->setValue(90.009);
        $scalarResultModel->save($sr);
        $daos = $scalarResultModel->getAll();
        $sr = $daos[0];
        $this->assertEquals(90.009, $sr->getValue());
    }
}
