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
include_once BASE_PATH . '/modules/dicomserver/constant/module.php';
/** RegistrationModel Base class */
abstract class Dicomserver_RegistrationModelBase extends Dicomserver_AppModel {

  /**
   * constructor
   */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'dicomserver_registration';
    $this->_key = 'registration_id';
    $this->_daoName = 'RegistrationDao';

    $this->_mainData = array(
      'registration_id' => array('type' => MIDAS_DATA),
      'item_id' => array('type' => MIDAS_DATA),
      'revision_id' => array('type' => MIDAS_DATA)
       );
    $this->initialize(); // required
    }

  /** Check registration information by an itemId */
  abstract function checkByItemId($itemId);

 /**
   * Register an item
   *
   * @param string $item_id
   * @return Dicomserver_RegistrationDao
   */
  function createRegistration($item_id)
    {
    $registrationDao = MidasLoader::newDao('RegistrationDao', 'dicomserver');
    $registrationDao->setItemId($item_id);
    $this->save($registrationDao);
    return $registrationDao;
    }


}  // end class Dicomserver_RegistrationModelBase
