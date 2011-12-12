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