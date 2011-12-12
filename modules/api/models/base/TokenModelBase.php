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
abstract class Api_TokenModelBase extends Api_AppModel
{
  /** constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'api_token';
    $this->_key = 'token_id';

    $this->_mainData = array(
        'token_id' => array('type' => MIDAS_DATA),
        'userapi_id' =>  array('type' => MIDAS_DATA),
        'token' =>  array('type' => MIDAS_DATA),
        'expiration_date' =>  array('type' => MIDAS_DATA),
        'userapi' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Userapi', 'module' => 'api', 'parent_column' => 'userapi_id', 'child_column' => 'userapi_id'),
        );
    $this->initialize(); // required
    } // end __construct()

  abstract function cleanExpired();

} // end class AssetstoreModelBase
?>
