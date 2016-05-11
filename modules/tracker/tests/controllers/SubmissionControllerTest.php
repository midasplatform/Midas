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

/** Tests SubmissionController. */
class Tracker_SubmissionControllerTest extends ControllerTestCase
{
    /** Set up tests. */
    public function setUp()
    {
        $this->setupDatabase(array('default')); // core dataset
        $this->setupDatabase(array('submission'), 'tracker'); // module dataset
        $this->enabledModules = array('tracker');

        parent::setUp();
    }

    /**
     * Test the submission controller csv action.
     */
    public function testCsvAction()
    {
        $usersFile = $this->loadData('User', 'default');
        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        /** @var UserDao $user1Dao */
        $user1Dao = $userModel->load(1);
        /** @var UserDao $user2Dao */
        $user2Dao = $userModel->load(2);

        // Expect this to fail because we pass no user.
        $this->resetAll();
        $this->params = array();
        $this->params['submissionUuid'] = 2234;
        $this->params['useSession'] = 1;
        $this->dispatchUrl('/tracker/submission/csv/', null, true);
        $this->assertTrue($this->getResponse()->hasExceptionOfCode(403));

        // Expect this to fail because the user doesn't have permissions on the community.
        $this->resetAll();
        $this->params = array();
        $this->params['submissionUuid'] = 2234;
        $this->params['useSession'] = 1;
        $this->dispatchUrl('/tracker/submission/csv/', $user2Dao, true);
        $this->assertTrue($this->getResponse()->hasExceptionOfCode(403));

        // Expect this to pass the access controls, but because the endpoint will
        // set headers to correctly return CSV, and our current PhpUnit prints output
        // before the controller is called, resulting in an error with code 2.  So if
        // we find a code 2, that means headers have been set by our controller action,
        // which indicates success in a roundabout way.
        $this->resetAll();
        $this->params = array();
        $this->params['submissionUuid'] = 2234;
        $this->params['useSession'] = 1;
        // User 1 is a member of the community.
        $this->dispatchUrl('/tracker/submission/csv/', $user1Dao, true);
        $this->assertTrue($this->getResponse()->hasExceptionOfCode(2));
    }
}
