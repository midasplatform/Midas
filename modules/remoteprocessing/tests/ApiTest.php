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

/** index controller tests*/
class ApiTest extends ControllerTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default', 'adminUser'));
    $this->_models = array('User', 'Item');
    $this->enabledModules = array('scheduler', 'remoteprocessing', 'api');
    parent::setUp();
    }

  private function _getSecurityKey()
    {
    $this->resetAll();
    $usersFile = $this->loadData('User', 'adminUser');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $this->params = array();
    $securityKey = uniqid();
    $this->params['securitykey'] = $securityKey;
    $this->params['submitConfig'] = 'true';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/remoteprocessing/config", $userDao);
    $this->resetAll();
    return $securityKey;
    }

  /** test manage */
  public function testAllApiSubmissionProcess()
    {
    $usersFile = $this->loadData('User', 'adminUser');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $itemFile = $this->loadData('Item', 'default');

    $revision = $this->Item->getLastRevision($itemFile[0]);

    // register (create user)
    $this->params = array();
    $this->params['securitykey'] = $this->_getSecurityKey();
    $this->params['os'] = MIDAS_REMOTEPROCESSING_OS_WINDOWS;
    $this->request->setMethod('POST');

    $this->dispatchUrI('/api/json?method=midas.remoteprocessing.registerserver');

    $jsonResults = $this->getBody();
    $this->resetAll();
    if(strpos($jsonResults, '{"stat":"ok"') === false)
      {
      $this->fail('Error json');
      }
    $results = JsonComponent::decode($jsonResults);

    $token = $results['data']['token'];
    $email = $results['data']['email'];
    $apikey = $results['data']['apikey'];

    // authenticate
    $this->params = array();
    $this->params['securitykey'] = $this->_getSecurityKey();
    $this->params['email'] = $email;
    $this->params['apikey'] = $apikey;
    $this->request->setMethod('POST');

    $this->dispatchUrI('/api/json?method=midas.remoteprocessing.registerserver');

    $jsonResults = $this->getBody();
    $this->resetAll();
    if(strpos($jsonResults, '{"stat":"ok"') === false)
      {
      $this->fail('Error json');
      }
    $results = JsonComponent::decode($jsonResults);

    $token = $results['data']['token'];

    // ask action
    $this->params = array();
    $this->params['token'] = $token;
    $this->params['os'] = MIDAS_REMOTEPROCESSING_OS_WINDOWS;
    $this->request->setMethod('POST');

    $this->dispatchUrI('/api/json?method=midas.remoteprocessing.keepaliveserver');

    $jsonResults = $this->getBody();
    $this->resetAll();
    if(strpos($jsonResults, '{"stat":"ok"') === false)
      {
      $this->fail('Error json');
      }
    $results = JsonComponent::decode($jsonResults);

    if($results['data']['action'] != 'wait')
      {
      $this->fail('Should be wait, was '.$results['data']['action']);
      }

    // add a job
    $scriptParams['script'] = 'script';
    $scriptParams['os'] = MIDAS_REMOTEPROCESSING_OS_WINDOWS;
    $scriptParams['condition'] = '';
    $scriptParams['params'] = array();
    Zend_Registry::get('notifier')->callback("CALLBACK_REMOTEPROCESSING_ADD_JOB", $scriptParams);

    $this->params = array();
    $this->params['token'] = $token;
    $this->params['os'] = MIDAS_REMOTEPROCESSING_OS_WINDOWS;
    $this->request->setMethod('POST');

    $this->dispatchUrI('/api/json?method=midas.remoteprocessing.keepaliveserver');

    $jsonResults = $this->getBody();
    $this->resetAll();
    if(strpos($jsonResults, '{"stat":"ok"') === false)
      {
      $this->fail('Error json');
      }
    $results = JsonComponent::decode($jsonResults);

    if($results['data']['action'] != 'process')
      {
      $this->fail('Should be process, was '.$results['data']['action']);
      }

    // send results
    $this->params = array();
    $this->params['token'] = $token;
    $this->params['os'] = MIDAS_REMOTEPROCESSING_OS_WINDOWS;
    $this->request->setMethod('POST');

    $this->dispatchUrI('/api/json?method=midas.remoteprocessing.resultsserver&testingmode=1');
    $jsonResults = $this->getBody();
    $this->resetAll();
    if(strpos($jsonResults, '{"stat":"ok"') === false)
      {
      $this->fail('Error json');
      }
    }

  }
