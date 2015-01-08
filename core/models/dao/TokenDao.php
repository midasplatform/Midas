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
 * API token DAO.
 *
 * @method int getTokenId()
 * @method void setTokenId(int $tokenId)
 * @method int getUserapiId()
 * @method void setUserapiId(int $userApiId)
 * @method string getToken()
 * @method void setToken(string $token)
 * @method string getExpirationDate()
 * @method void setExpirationDate(string $expirationDate)
 * @method UserapiDao getUserapi()
 * @method void setUserapi(UserapiDao $userApi)
 * @package Core\DAO
 */
class TokenDao extends AppDao
{
    /** @var string */
    public $_model = 'Token';
}
