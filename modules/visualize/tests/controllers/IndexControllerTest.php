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

/** index controller test */
class Visualize_IndexControllerTest extends ControllerTestCase
{
    /** set up tests */
    public function setUp()
    {
        $this->setupDatabase(array('default'));
        $this->enabledModules = array('visualize');
        parent::setUp();
    }

    /** test index action */
    public function testIndexAction()
    {
        /** @var GroupModel $groupModel */
        $groupModel = MidasLoader::loadModel('Group');

        /** @var ItempolicygroupModel $itempolicygroupModel */
        $itempolicygroupModel = MidasLoader::loadModel('Itempolicygroup');

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');

        /** @var FolderModel $folderModel */
        $folderModel = MidasLoader::loadModel('Folder');

        /** @var UploadComponent $uploadComponent */
        $uploadComponent = MidasLoader::loadComponent('Upload');

        $usersFile = $this->loadData('User', 'default');
        $userDao = $userModel->load($usersFile[0]->getKey());

        Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));

        $privateFolder = $folderModel->load(1002);
        $item = $uploadComponent->createUploadedItem(
            $userDao,
            "test.png",
            BASE_PATH.'/tests/testfiles/search.png',
            $privateFolder,
            null,
            '',
            true
        );
        $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
        $itempolicygroupModel->createPolicy($anonymousGroup, $item, MIDAS_POLICY_READ);
        $this->params['itemId'] = $item->getKey();
        $this->dispatchUrl("/visualize/index/index");
        $this->assertController("index");

        $this->dispatchUrl("/visualize/image/index");
        $this->assertController("image");

        $this->dispatchUrl("/visualize/media/index");
        $this->assertController("media");

        $this->dispatchUrl("/visualize/pdf/index");
        $this->assertController("pdf");

        $this->dispatchUrl("/visualize/txt/index");
        $this->assertController("txt");

        $this->dispatchUrl("/visualize/webgl/index");
        $this->assertController("webgl");
    }
}
