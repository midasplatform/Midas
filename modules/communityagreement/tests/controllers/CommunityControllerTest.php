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

/** Community controller test */
class Communityagreement_CommunityControllerTest extends ControllerTestCase
{
    /** set up tests */
    public function setUp()
    {
        $this->setupDatabase(array('default'), 'communityagreement');
        $this->setupDatabase(array('default'));
        $this->_models = array('Community', 'User');
        $this->_moduleModels = array('Agreement');
        $this->enabledModules = array('communityagreement');
        parent::setUp();
    }

    /** test agreementdialog */
    public function testAgreementdialogAction()
    {
        $communitiesFile = $this->loadData('Community', 'default');
        $community_id = $communitiesFile[0]->getKey();

        $this->getRequest()->setMethod('POST');
        $page = '/communityagreement/community/agreementdialog?communityId='.$community_id;
        $this->dispatchUrI($page);

        $this->assertModule("communityagreement");
        $this->assertController("community");
        $this->assertAction("agreementdialog");

        $body = $this->getBody();
        if (strpos($body, "Community agreement for Community test User 1") === false
        ) {
            $this->fail('Unable to find body element');
        }
    }

    /** test agreementtab */
    public function testAgreementtabAction()
    {
        $communitiesFile = $this->loadData('Community', 'default');
        $community_id = $communitiesFile[0]->getKey();
        $usersFile = $this->loadData('User', 'default');
        $admin = $this->User->load($usersFile[2]->getKey());

        $this->params = array();
        $this->params['email'] = 'user1@user1.com';
        $this->params['password'] = 'test';
        $this->request->setMethod('POST');
        $this->dispatchUrI("/user/login");

        $this->resetAll();
        $page = '/communityagreement/community/agreementtab?communityId='.$community_id;
        $this->params = array();
        $this->getRequest()->setMethod('GET');
        $this->dispatchUrI($page, null, true);

        $this->assertController("error");
        $this->assertAction("error");

        $this->resetAll();
        $this->params['agreement'] = 'test agreement tab';
        $this->params['communityId'] = $community_id;
        $this->getRequest()->setMethod('POST');
        $this->dispatchUrI($page, $admin);

        $agreementModel = MidasLoader::loadModel('Agreement', 'communityagreement');
        $saved_agreement = $agreementModel->getByCommunityId($community_id)->getAgreement();
        $this->assertEquals('test agreement tab', $saved_agreement);

        // Make sure anonymous users cannot change agreement
        $this->resetAll();
        $this->params['agreement'] = 'should not work';
        $this->params['communityId'] = $community_id;
        $this->getRequest()->setMethod('POST');
        $this->dispatchUrI($page, null, true);

        $saved_agreement = $agreementModel->getByCommunityId($community_id)->getAgreement();
        $this->assertNotEquals('should not work', $saved_agreement);
    }
}
