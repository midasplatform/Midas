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

require_once BASE_PATH.'/modules/landingpage/models/AppModel.php';

/** demo base model */
abstract class Landingpage_TextModelBase extends Landingpage_AppModel
{
    /** constructor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'landingpage_text';
        $this->_key = 'landingpage_id';

        $this->_mainData = array(
            'landingpage_id' => array('type' => MIDAS_DATA),
            'text' => array('type' => MIDAS_DATA),
        );
        $this->initialize(); // required
    }

    /** Get all */
    abstract public function getAll();
}
