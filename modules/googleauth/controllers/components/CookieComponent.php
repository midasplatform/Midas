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

/** Cookie component for the googleauth module. */
class Googleauth_CookieComponent extends AppComponent
{
    /**
     * Send a cookie containing the given Google access token.
     *
     * @param Zend_Controller_Request_Http $request HTTP request
     * @param false|Google_Client $client Google API client
     * @param DateTime $expires time the cookie expires
     */
    public function setAccessTokenCookie($request, $client, $expires)
    {
        $value = $client === false ? false : $client->getAccessToken();
        UtilityComponent::setCookie($request, GOOGLE_AUTH_ACCESS_TOKEN_COOKIE_NAME, $value, $expires);
    }

    /**
     * Send a cookie containing the id and API key of the given user.
     *
     * @param Zend_Controller_Request_Http $request HTTP request
     * @param UserDao $userDao user DAO
     * @param DateTime $expires time the cookie expires
     */
    public function setUserCookie($request, $userDao, $expires)
    {
        /** @var UserapiModel $userApiModel */
        $userApiModel = MidasLoader::loadModel('Userapi');
        $userApiDao = $userApiModel->getByAppAndUser('Default', $userDao);
        $value = 'googleauth:'.$userDao->getUserId().':'.md5($userApiDao->getApikey());
        UtilityComponent::setCookie($request, MIDAS_USER_COOKIE_NAME, $value, $expires);
    }
}
