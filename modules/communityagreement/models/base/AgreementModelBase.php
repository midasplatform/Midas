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