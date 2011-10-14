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
/** test agreement model*/
class AgreementModelTest extends DatabaseTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    //$this->setupDatabase(array('default')); //core dataset

    $this->enabledModules = array('communityagreement');
    $this->setupDatabase(array('default')); //core dataset
    $this->setupDatabase(array('default'), 'communityagreement'); // module dataset
    parent::setUp();
    }

  /** test AgreementModel::GetAll .*/
  public function testGetAll()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $agreementModel = $modelLoad->loadModel('Agreement', 'communityagreement');

    $daos = $agreementModel->getAll();
    $this->assertEquals(1, count($daos));
    }

  /** test AgreementModel::getByCommunityId*/
  public function testGetByCommunityId()
    {
    $modelLoad = new MIDAS_ModelLoader();
    $agreementModel = $modelLoad->loadModel('Agreement', 'communityagreement');

    $dao = $agreementModel->getByCommunityId('2000');
    $this->assertEquals(1, count($dao));
    $this->assertEquals('Community agreement for Community test User 1', $dao->getAgreement());
    }

  }