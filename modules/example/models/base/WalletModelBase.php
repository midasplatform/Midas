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
/** WalletModel Base class */
class Example_WalletModelBase extends Example_AppModel
  {

  /**
   * constructor
   */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'example_wallet';
    $this->_key = 'example_wallet_id';

    $this->_mainData = array(
      'example_wallet_id' => array('type' => MIDAS_DATA),
      'user_id' => array('type' => MIDAS_DATA),
      'dollars' => array('type' => MIDAS_DATA),
      'credit_card_count' => array('type' => MIDAS_DATA),
      'user' =>  array('type' => MIDAS_MANY_TO_ONE,
                        'model' => 'User',
                        'parent_column' => 'user_id',
                        'child_column' => 'user_id'));
    $this->initialize(); // required
    }


  /** Create a wallet
   * @return WalletDao */
  function createWallet($userDao, $dollars)
    {
    $wallet = MidasLoader::newDao('WalletDao', 'example');
    $wallet->setUserId($userDao->getKey());
    $wallet->setDollars($dollars);
    $this->save($wallet);
    return $wallet;
    } // end createWallet()

  } // end class
