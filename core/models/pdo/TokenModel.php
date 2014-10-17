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

require_once BASE_PATH.'/core/models/base/TokenModelBase.php';

/** Api Token model implementation */
class TokenModel extends TokenModelBase
{
    /** Remove all expired api tokens */
    public function cleanExpired()
    {
        $sql = $this->database->select()->where('expiration_date < ?', date("Y-m-d H:i:s"));
        $rowset = $this->database->fetchAll($sql);
        foreach ($rowset as $row) {
            $tmpDao = $this->initDao('Token', $row);
            parent::delete($tmpDao);
        }
    }
}
