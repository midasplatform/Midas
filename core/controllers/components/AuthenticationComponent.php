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

/** Web API Authentication Component */
class AuthenticationComponent extends AppComponent
{
    /**
     * Gets the user dao from either the session (if via ajax)
     * or using token-based authentication otherwise.
     * Returns false for anonymous users.
     */
    public function getUser($args, $sessionDao)
    {
        if (array_key_exists('useSession', $args)) {
            return $sessionDao;
        } else {
            // Attempt to let modules handle alternative API authentication methods. If the module returns an array
            // with a 'userDao' key set to either null or a valid user dao, that value is returned in lieu of token or session user.
            $callbacks = Zend_Registry::get('notifier')->callback(
                'CALLBACK_API_AUTH_INTERCEPT',
                array('args' => $args)
            );
            foreach ($callbacks as $response) {
                if (is_array($response) && array_key_exists('userDao', $response)
                ) {
                    return $response['userDao'];
                }
            }

            if (!array_key_exists('token', $args)) {
                return 0;
            }
            $token = $args['token'];
            $userApiModel = MidasLoader::loadModel('Userapi');
            $userapiDao = $userApiModel->getUserapiFromToken($token);
            if (!$userapiDao) {
                throw new Exception('Invalid token', MIDAS_INVALID_TOKEN);
            }
            $userid = $userapiDao->getUserId();
            if ($userid == 0) {
                return false;
            }
            $userModel = MidasLoader::loadModel('User');
            $userDao = $userModel->load($userid);

            // Set the session in the notifier so callback handlers can use it
            if (!headers_sent()) {
                session_start();
            }
            $userSession = new Zend_Session_Namespace('Auth_User');
            $userSession->setExpirationSeconds(60 * Zend_Registry::get('configGlobal')->session->lifetime);
            $userSession->Dao = $userDao;
            Zend_Registry::set('notifier', new MIDAS_Notifier(true, $userSession));
            session_write_close();

            return $userDao;
        }
    }
}
