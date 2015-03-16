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

include_once BASE_PATH.'/modules/dicomserver/constant/module.php';

/** RegistrationModel Base class */
abstract class Dicomserver_RegistrationModelBase extends Dicomserver_AppModel
{
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
            'revision_id' => array('type' => MIDAS_DATA),
        );
        $this->initialize(); // required
    }

    /** Check registration information by an itemId */
    abstract public function checkByItemId($itemId);

    /**
     * Register an item
     *
     * @param  string $item_id
     * @return Dicomserver_RegistrationDao
     */
    public function createRegistration($item_id)
    {
        /** @var Dicomserver_RegistrationDao $registrationDao */
        $registrationDao = MidasLoader::newDao('RegistrationDao', 'dicomserver');
        $registrationDao->setItemId($item_id);
        $this->save($registrationDao);

        return $registrationDao;
    }
}
