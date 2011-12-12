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
/**
 * Communityagreement_AgreementModelBase
 *
 * agreement base model
 *
 * @category   Midas modules
 * @package    communityagreement
 */
class Communityagreement_AgreementModelBase extends Communityagreement_AppModel
{
  /** constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'communityagreement_agreement';
    $this->_key = 'agreement_id';

    $this->_mainData = array(
        'agreement_id' =>  array('type' => MIDAS_DATA),
        'community_id' =>  array('type' => MIDAS_DATA),
        'agreement' => array('type' => MIDAS_DATA),
        );
    $this->initialize(); // required
    } // end __construct()

  /**
   * Create a community agreement
   *
   * @param string $community_id
   * @param string $agreement
   * @return Communityagreement_AgreementDao
   */
  function createAgreement($community_id, $agreement)
    {
    $this->loadDaoClass('AgreementDao', 'communityagreement');
    $agreementDao = new Communityagreement_AgreementDao();
    $agreementDao->setCommunityId($community_id);
    $agreementDao->setAgreement($agreement);
    $this->save($agreementDao);
    return $agreementDao;
    }

} // end class AgreementModelBase