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

require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';

/**
 * Generic notification manager class.
 *
 * @package Core\Notification
 */
class Notification extends MIDAS_Notification
{
    /** @var array */
    public $_components = array('Utility', 'Authentication');

    /** @var array */
    public $_models = array('User', 'Item');

    /** Initialize this notification manager. */
    public function init()
    {
        $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getDasboard');
        $this->addCallBack('CALLBACK_CORE_GET_CONFIG_TABS', 'getConfigTabs');
        $this->addCallBack('CALLBACK_CORE_PASSWORD_CHANGED', 'setDefaultWebApiKey');
        $this->addCallBack('CALLBACK_CORE_NEW_USER_ADDED', 'setDefaultWebApiKey');
        $this->addCallBack('CALLBACK_CORE_USER_DELETED', 'handleUserDeleted');
        $this->addCallBack('CALLBACK_CORE_PARAMETER_AUTHENTICATION', 'tokenAuth');
    }

    /**
     * Handle the get dashboard callback.
     *
     * @return array map of dashboard fields to their values
     */
    public function getDasboard()
    {
        return array(
            'Data Folder Writable' => array(is_writable(UtilityComponent::getDataDirectory())),
            // pass in empty string since we want to check the overall root temp directory
            'Temporary Folder Writable' => array(is_writable(UtilityComponent::getTempDirectory(''))),
        );
    }

    /**
     * Handle the get configuration tabs callback.
     *
     * @param array $params parameters
     * @return array map from tab names to URLs of the tab content
     */
    public function getConfigTabs($params)
    {
        $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();

        return array('API' => $webroot.'/apikey/usertab?userId='.$params['user']->getKey());
    }

    /**
     * Handle the password changed callback. Reset the default API of the user.
     *
     * @param array $params parameters
     * @throws Zend_Exception
     */
    public function setDefaultWebApiKey($params)
    {
        if (!isset($params['userDao'])) {
            throw new Zend_Exception('Error: userDao parameter required');
        }

        /** @var UserapiModel $userApiModel */
        $userApiModel = MidasLoader::loadModel('Userapi');
        $userApiModel->createDefaultApiKey($params['userDao']);
    }

    /**
     * Handle the user deleted callback. Delete the API keys of the user.
     *
     * @param array $params parameters
     * @throws Zend_Exception
     */
    public function handleUserDeleted($params)
    {
        if (!isset($params['userDao'])) {
            throw new Zend_Exception('Error: userDao parameter required');
        }

        /** @var UserapiModel $userApiModel */
        $userApiModel = MidasLoader::loadModel('Userapi');
        $apiKeys = $userApiModel->getByUser($params['userDao']);

        foreach ($apiKeys as $apiKey) {
            $userApiModel->delete($apiKey);
        }
    }

    /**
     * Handle the parameter authentication callback. When we redirect from the
     * API for downloads, we add a user token as a parameter, and the
     * controller makes a callback here to get the user.
     *
     * @param array $params parameters
     * @return false|UserDao user DAO or false on failure
     */
    public function tokenAuth($params)
    {
        return $this->Component->Authentication->getUser(array('token' => $params['authToken']), null);
    }
}
