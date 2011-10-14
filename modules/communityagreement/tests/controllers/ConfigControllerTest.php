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

// need to include the module constant for this test
require_once str_replace('tests', 'constant', str_replace('controllers', 'module.php', dirname(__FILE__)));

/** config controller test*/
class ConfigControllerTest extends ControllerTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'), 'communityagreement');
    $this->setupDatabase(array('default'));
    $this->_models = array('Community', 'User');
    $this->_moduleModels = array('Agreement');
    $this->enabledModules = array('communityagreement');
    parent::setUp();
    }

  /** test agreementdialog*/
  public function testAgreementdialogAction()
    {
    $communitiesFile = $this->loadData('Community', 'default');
    $community_id = $communitiesFile[0]->getKey();

    $this->getRequest()->setMethod('POST');
    $page = '/communityagreement/config/agreementdialog?communityId='.$community_id;
    $this->dispatchUrI($page);

    $this->assertModule("communityagreement");
    $this->assertController("config");
    $this->assertAction("agreementdialog");

    $body = $this->getBody();
    if(strpos($body, "Community agreement for Community test User 1") === false)
      {
      $this->fail('Unable to find body element');
      }
    }

  /** test agreementtab*/
  public function testAgreementtabAction()
    {
    $communitiesFile = $this->loadData('Community', 'default');
    $community_id = $communitiesFile[0]->getKey();

    $this->params = array();
    $this->params['email'] = 'user1@user1.com';
    $this->params['password'] = 'test';
    $this->request->setMethod('POST');
    $this->dispatchUrI("/user/login");

    $page = '/communityagreement/config/agreementtab?communityId='.$community_id;
    $this->params = array();
    $this->getRequest()->setMethod('GET');
    $this->dispatchUrI($page);

    $this->assertModule("communityagreement");
    $this->assertController("config");
    $this->assertAction("agreementtab");

    $this->params['agreement'] = 'test agreement tab';
    $this->getRequest()->setMethod('POST');
    $this->dispatchUrI($page);

    $modelLoad = new MIDAS_ModelLoader();
    $agreementModel = $modelLoad->loadModel('Agreement', 'communityagreement');
    $saved_agreement = $agreementModel->getByCommunityId($community_id)->getAgreement();
    $this->assertEquals('test agreement tab', $saved_agreement);
    }
  }