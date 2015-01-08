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
 * Registration DAO class for the dicomserver module.
 *
 * @method int getRegistrationId()
 * @method void setRegistrationId(int $registrationId)
 * @method int getItemId()
 * @method void setItemId(int $itemId)
 * @method int getRevisionId()
 * @method void setRevisionId(int $revisionId)
 * @package Modules\Dicomserver\DAO
 */
class Dicomserver_RegistrationDao extends AppDao
{
    /** @var string */
    public $_model = 'Registration';

    /** @var string */
    public $_module = 'dicomserver';
}
