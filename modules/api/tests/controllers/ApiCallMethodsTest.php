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

/** Tests the functionality of the web API methods */
class ApiCallMethodsTest extends ControllerTestCase
  {
  /** set up tests */
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'api'); // module dataset
    $this->enabledModules = array('api');
    $this->_models = array('User', 'Folder', 'Item', 'ItemRevision', 'Assetstore', 'Bitstream');
    $this->_daos = array();

    parent::setUp();
    }

  /** Invoke the JSON web API */
  protected function _callJsonApi($sessionUser = null, $method = 'POST')
    {
    $this->request->setMethod($method);
    $this->dispatchUrI($this->webroot.'api/json', $sessionUser);
    return json_decode($this->getBody());
    }

  /** Make sure we got a good response from a web API call */
  protected function _assertStatusOk($resp)
    {
    $this->assertNotEquals($resp, false);
    $this->assertEquals($resp->message, '');
    $this->assertEquals($resp->stat, 'ok');
    $this->assertEquals($resp->code, 0);
    $this->assertTrue(isset($resp->data));
    }

  /** Make sure we failed with a given message from the API call */
  protected function _assertStatusFail($resp, $code, $message = false)
    {
    $this->assertNotEquals($resp, false);
    $this->assertEquals($resp->stat, 'fail');
    $this->assertEquals($resp->code, $code);
    if($message !== false)
      {
      $this->assertEquals($resp->message, $message);
      }
    }

  /** helper function to login as the passed in user. */
  protected function _loginAsUser($userDao)
    {
    $userApiModel = MidasLoader::loadModel('Userapi', 'api');
    $userApiModel->createDefaultApiKey($userDao);
    $apiKey = $userApiModel->getByAppAndUser('Default', $userDao)->getApikey();

    $this->params['method'] = 'midas.login';
    $this->params['email'] = $userDao->getEmail();
    $this->params['appname'] = 'Default';
    $this->params['apikey'] = $apiKey;

    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals(strlen($resp->data->token), 40);

    // **IMPORTANT** This will clear any params that were set before this function was called
    $this->resetAll();
    return $resp->data->token;
    }

  /** Authenticate using the default api key for user 1 */
  protected function _loginAsNormalUser()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    return $this->_loginAsUser($userDao);
    }

  /** Authenticate using the default api key */
  protected function _loginAsAdministrator()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[2]->getKey());
    return $this->_loginAsUser($userDao);
    }

  /**
   * helper function to initialize the passed in resources to the given privacy state.
   */
  protected function initializePrivacyStatus($testFolders, $testItems, $desiredPrivacyStatus)
    {
    $groupModel = MidasLoader::loadModel('Group');
    $anonymousGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
    $folderpolicygroupModel = MidasLoader::loadModel("Folderpolicygroup");
    $itempolicygroupModel = MidasLoader::loadModel("Itempolicygroup");

    foreach($testFolders as $folder)
      {
      if($desiredPrivacyStatus != $folderpolicygroupModel->computePolicyStatus($folder))
        {
        if($desiredPrivacyStatus == MIDAS_PRIVACY_PUBLIC)
          {
          $policyDao = $folderpolicygroupModel->createPolicy($anonymousGroup, $folder, MIDAS_POLICY_READ);
          }
        else
          {
          $policyDao = $folderpolicygroupModel->getPolicy($anonymousGroup, $folder);
          $folderpolicygroupModel->delete($policyDao);
          }
        }
      $this->assertEquals($folderpolicygroupModel->computePolicyStatus($folder), $desiredPrivacyStatus, $folder->getName() . " has wrong privacy value after initialization");
      }

    foreach($testItems as $item)
      {
      if($desiredPrivacyStatus != $itempolicygroupModel->computePolicyStatus($item))
        {
        if($desiredPrivacyStatus == MIDAS_PRIVACY_PUBLIC)
          {
          $policyDao = $itempolicygroupModel->createPolicy($anonymousGroup, $item, MIDAS_POLICY_READ);
          }
        else
          {
          $policyDao = $itempolicygroupModel->getPolicy($anonymousGroup, $item);
          $itempolicygroupModel->delete($policyDao);
          }
        }
      $this->assertEquals($itempolicygroupModel->computePolicyStatus($item), $desiredPrivacyStatus, $item->getName() . " has wrong privacy value after initialization");
      }
    }

  /**
   * helper function to ensure that passed in resources have the given privacy.
   */
  protected function assertPrivacyStatus($testFolders, $testItems, $expectedPrivacyStatus)
    {
    $folderModel = MidasLoader::loadModel('Folder');
    $folderpolicygroupModel = MidasLoader::loadModel("Folderpolicygroup");
    $itemModel = MidasLoader::loadModel('Item');
    $itempolicygroupModel = MidasLoader::loadModel("Itempolicygroup");
    foreach($testFolders as $folder)
      {
      $folder = $folderModel->load($folder->getFolderId());
      $this->assertEquals($folderpolicygroupModel->computePolicyStatus($folder), $expectedPrivacyStatus, $folder->getName() . " has wrong privacy value");
      }
    foreach($testItems as $item)
      {
      $item = $itemModel->load($item->getItemId());
      $this->assertEquals($itempolicygroupModel->computePolicyStatus($item), $expectedPrivacyStatus, $item->getName() . " has wrong privacy value");
      }
    }

  /**
   * helper function to ensure that passed in resources have a policygroup with the
   * given group and policy.
   */
  protected function assertPolicygroupExistence($testFolders, $testItems, $group, $policyCode)
    {
    $folderModel = MidasLoader::loadModel('Folder');
    $folderpolicygroupModel = MidasLoader::loadModel("Folderpolicygroup");
    $itemModel = MidasLoader::loadModel('Item');
    $itempolicygroupModel = MidasLoader::loadModel("Itempolicygroup");
    foreach($testFolders as $folder)
      {
      $folder = $folderModel->load($folder->getFolderId());
      $folderpolicygroup = $folderpolicygroupModel->getPolicy($group, $folder);
      if($folderpolicygroup !== false)
        {
        $message = $folder->getName() . ' has the wrong policy for group '. $group->getGroupId() .' policy '. $policyCode;
        $this->assertEquals($folderpolicygroup->getPolicy(), $policyCode, $message);
        }
      else
        {
        $message = $folder->getName() . ' does not have any policy for group '. $group->getGroupId() .' policy '. $policyCode;
        $this->assertTrue(false, $message);
        }
      }
    foreach($testItems as $item)
      {
      $item = $itemModel->load($item->getItemId());
      $itempolicygroup = $itempolicygroupModel->getPolicy($group, $item);
      if($itempolicygroup !== false)
        {
        $message = $item->getName() . ' has the wrong policy for group '. $group->getGroupId() .' policy '. $policyCode;
        $this->assertEquals($itempolicygroup->getPolicy(), $policyCode, $message);
        }
      else
        {
        $message = $item->getName() . ' does not have any policy for group '. $group->getGroupId() .' policy '. $policyCode;
        $this->assertTrue(false, $message);
        }
      }
    }

  /**
   * helper function to ensure that passed in resources do not have a policygroup
   * for the given group.
   */
  protected function assertPolicygroupNonexistence($testFolders, $testItems, $group)
    {
    $folderModel = MidasLoader::loadModel('Folder');
    $folderpolicygroupModel = MidasLoader::loadModel("Folderpolicygroup");
    $itemModel = MidasLoader::loadModel('Item');
    $itempolicygroupModel = MidasLoader::loadModel("Itempolicygroup");
    foreach($testFolders as $folder)
      {
      $folder = $folderModel->load($folder->getFolderId());
      $folderpolicygroup = $folderpolicygroupModel->getPolicy($group, $folder);
      if($folderpolicygroup !== false)
        {
        $message = $folder->getName() . ' should not have a policy for group '. $group->getGroupId();
        $this->assertTrue(false, $message);
        }
      }
    foreach($testItems as $item)
      {
      $item = $itemModel->load($item->getItemId());
      $itempolicygroup = $itempolicygroupModel->getPolicy($group, $item);
      if($itempolicygroup !== false)
        {
        $message = $item->getName() . ' should not have a policy for group '. $group->getGroupId();
        $this->assertTrue(false, $message);
        }
      }
    }

  /**
   * helper function to ensure that passed in resources have a policyuser with the
   * given user and policy.
   */
  protected function assertPolicyuserExistence($testFolders, $testItems, $user, $policyCode)
    {
    $folderModel = MidasLoader::loadModel('Folder');
    $folderpolicyuserModel = MidasLoader::loadModel("Folderpolicyuser");
    $itemModel = MidasLoader::loadModel('Item');
    $itempolicyuserModel = MidasLoader::loadModel("Itempolicyuser");
    foreach($testFolders as $folder)
      {
      $folder = $folderModel->load($folder->getFolderId());
      $folderpolicyuser = $folderpolicyuserModel->getPolicy($user, $folder);
      if($folderpolicyuser !== false)
        {
        $message = $folder->getName() . ' has the wrong policy for user '. $user->getUserId() .' policy '. $policyCode;
        $this->assertEquals($folderpolicyuser->getPolicy(), $policyCode, $message);
        }
      else
        {
        $message = $folder->getName() . ' does not have any policy for user '. $user->getUserId() .' policy '. $policyCode;
        $this->assertTrue(false, $message);
        }
      }
    foreach($testItems as $item)
      {
      $item = $itemModel->load($item->getItemId());
      $itempolicyuser = $itempolicyuserModel->getPolicy($user, $item);
      if($itempolicyuser !== false)
        {
        $message = $item->getName() . ' has the wrong policy for user '. $user->getUserId() .' policy '. $policyCode;
        $this->assertEquals($itempolicyuser->getPolicy(), $policyCode, $message);
        }
      else
        {
        $message = $item->getName() . ' does not have any policy for user '. $user->getUserId() .' policy '. $policyCode;
        $this->assertTrue(false, $message);
        }
      }
    }

  /**
   * helper function to ensure that passed in resources do not have a policyuser
   * for the given user.
   */
  protected function assertPolicyuserNonexistence($testFolders, $testItems, $user)
    {
    $folderModel = MidasLoader::loadModel('Folder');
    $folderpolicyuserModel = MidasLoader::loadModel("Folderpolicyuser");
    $itemModel = MidasLoader::loadModel('Item');
    $itempolicyuserModel = MidasLoader::loadModel("Itempolicyuser");
    foreach($testFolders as $folder)
      {
      $folder = $folderModel->load($folder->getFolderId());
      $folderpolicyuser = $folderpolicyuserModel->getPolicy($user, $folder);
      if($folderpolicyuser !== false)
        {
        $message = $folder->getName() . ' should not have a policy for user '. $user->getUserId();
        $this->assertTrue(false, $message);
        }
      }
    foreach($testItems as $item)
      {
      $item = $itemModel->load($item->getItemId());
      $itempolicyuser = $itempolicyuserModel->getPolicy($user, $item);
      if($itempolicyuser !== false)
        {
        $message = $item->getName() . ' should not have a policy for user '. $user->getUserId();
        $this->assertTrue(false, $message);
        }
      }
    }

  }
