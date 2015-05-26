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

require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';

/** Component for api methods */
class Remoteprocessing_ApiComponent extends AppComponent
{
    public $moduleName = 'remoteprocessing';

    /**
     * Register a server.
     *
     * @param email (Optional)
     * @param apikey (Optional)
     * @param securitykey Set in configuration
     * @param os (Optional) Operating System
     * @return Array (token, apikey and email)
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function registerserver($args)
    {
        $os = '';
        $apiKey = '';
        $email = '';
        $securityKey = '';
        if (isset($args['os'])) {
            $os = $args['os'];
        }
        if (isset($args['apikey'])) {
            $apiKey = $args['apikey'];
        }
        if (isset($args['email'])) {
            $email = $args['email'];
        }
        if (isset($args['securitykey'])) {
            $securityKey = $args['securitykey'];
        }

        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $checkSecurityKey = $settingModel->getValueByName(MIDAS_REMOTEPROCESSING_SECURITY_KEY_KEY, $this->moduleName);

        if (empty($securityKey) || $securityKey != $checkSecurityKey) {
            throw new Exception('Error security key. '.$securityKey.' '.$checkSecurityKey, MIDAS_INVALID_PARAMETER);
        }

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');

        /** @var GroupModel $groupModel */
        $groupModel = MidasLoader::loadModel('Group');

        /** @var UserapiModel $userApiModel */
        $userApiModel = MidasLoader::loadModel('Userapi');

        if (empty($apiKey)) {
            if (empty($os)) {
                throw new Exception('Error os parameter.', MIDAS_INVALID_PARAMETER);
            }

            /** @var RandomComponent $randomComponent */
            $randomComponent = MidasLoader::loadComponent('Random');
            $email = 'some.user@example.org';
            $userDao = $userModel->createUser($email, $randomComponent->generateString(32), 'Processing', 'Server');
            $userDao->setPrivacy(MIDAS_USER_PRIVATE);
            $userDao->setCompany($os); // used to set operating system
            $userModel->save($userDao);
            $serverGroup = $groupModel->load(MIDAS_GROUP_SERVER_KEY);
            $groupModel->addUser($serverGroup, $userDao);

            $userapiDao = $userApiModel->getByAppAndUser('remoteprocessing', $userDao);
            if ($userapiDao == false) {
                $userapiDao = $userApiModel->createKey($userDao, 'remoteprocessing', '100');
            }

            $apiKey = $userapiDao->getApikey();

            Zend_Registry::get('notifier')->callback('CALLBACK_REMOTEPROCESSING_CREATESERVER', $userDao->toArray());
        }

        $tokenDao = $userApiModel->getToken($email, $apiKey, 'remoteprocessing');
        if (empty($tokenDao)) {
            throw new Exception('Unable to authenticate. Please check credentials.', MIDAS_INVALID_PARAMETER);
        }

        $data['token'] = $tokenDao->getToken();
        $data['email'] = $email;
        $data['apikey'] = $apiKey;

        return $data;
    }

    /**
     * The client ping Midas Server and the server tells it what it should do.
     *
     * @param token
     * @return Array
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function keepaliveserver($args)
    {
        $authComponent = MidasLoader::loadComponent('Authentication');
        $userDao = $authComponent->getUser($args, Zend_Registry::get('userSession')->Dao);
        if ($userDao == false) {
            throw new Exception(
                'Unable to authenticate as a server. Please check credentials.', MIDAS_INVALID_PARAMETER
            );
        }

        if (!isset($args['os'])) {
            throw new Exception('Please set the os', MIDAS_INVALID_PARAMETER);
        }

        /** @var GroupModel $groupModel */
        $groupModel = MidasLoader::loadModel('Group');
        $groupServer = $groupModel->load(MIDAS_GROUP_SERVER_KEY);
        $users = $groupServer->getUsers();

        $isServer = false;
        foreach ($users as $user) {
            if ($user->getKey() == $userDao->getKey()) {
                $isServer = true;
            }
        }

        if ($isServer == false) {
            throw new Exception(
                'Unable to authenticate as a server. Please check credentials.', MIDAS_INVALID_PARAMETER
            );
        }

        /** @var Remoteprocessing_JobModel $jobModel */
        $jobModel = MidasLoader::loadModel('Job', 'remoteprocessing');
        $jobs = $jobModel->getBy($args['os'], '');

        if (empty($jobs)) {
            $paramsReturn['action'] = 'wait';
        } else {
            $paramsReturn['action'] = 'process';
            $paramsReturn['params'] = JsonComponent::decode($jobs[0]->getParams());
            $paramsReturn['script'] = $jobs[0]->getScript();
            $paramsReturn['params']['job_id'] = $jobs[0]->getKey();

            $paramsJob = $paramsReturn['params'];
            $paramsReturn['params'] = JsonComponent::encode($paramsReturn['params']);
            $jobs[0]->setStatus(MIDAS_REMOTEPROCESSING_STATUS_STARTED);
            $jobModel->save($jobs[0]);

            /** @var ItempolicyuserModel $itempolicyuserModel */
            $itempolicyuserModel = MidasLoader::loadModel('Itempolicyuser');

            /** @var FolderpolicyuserModel $folderpolicyuserModel */
            $folderpolicyuserModel = MidasLoader::loadModel('Folderpolicyuser');

            /** @var ItemModel $itemModel */
            $itemModel = MidasLoader::loadModel('Item');

            /** @var FolderModel $folderModel */
            $folderModel = MidasLoader::loadModel('Folder');

            // set policies
            if (isset($paramsJob['input'])) {
                foreach ($paramsJob['input'] as $itemId) {
                    $item = $itemModel->load($itemId);
                    if ($item != false) {
                        $itempolicyuserModel->createPolicy($userDao, $item, MIDAS_POLICY_READ);
                    }
                }
            }
            if (isset($paramsJob['ouputFolders'])) {
                foreach ($paramsJob['ouputFolders'] as $folderId) {
                    $folder = $folderModel->load($folderId);
                    if ($folder != false) {
                        $folderpolicyuserModel->createPolicy($userDao, $folder, MIDAS_POLICY_WRITE);
                    }
                }
            }
        }

        return $paramsReturn;
    }

    /**
     * The client sends the results to Midas Server (put request).
     *
     * @param token
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function resultsserver($args)
    {
        $testingmode = false;
        if (isset($_GET['testingmode']) && $_GET['testingmode'] == 1) {
            $testingmode = true;
        }
        if (!$testingmode && $_SERVER['REQUEST_METHOD'] != 'POST') {
            throw new Exception('Should be a put request.', MIDAS_INVALID_PARAMETER);
        }

        $authComponent = MidasLoader::loadComponent('Authentication');
        $userDao = $authComponent->getUser($args, Zend_Registry::get('userSession')->Dao);
        if ($userDao == false) {
            throw new Exception(
                'Unable to authenticate as a server. Please check credentials.', MIDAS_INVALID_PARAMETER
            );
        }

        /** @var GroupModel $groupModel */
        $groupModel = MidasLoader::loadModel('Group');
        $groupServer = $groupModel->load(MIDAS_GROUP_SERVER_KEY);
        $users = $groupServer->getUsers();

        $isServer = false;
        foreach ($users as $user) {
            if ($user->getKey() == $userDao->getKey()) {
                $isServer = true;
            }
        }

        if ($isServer == false) {
            throw new Exception(
                'Unable to authenticate as a server. Please check credentials.', MIDAS_INVALID_PARAMETER
            );
        }

        if (!file_exists(UtilityComponent::getTempDirectory().'/remoteprocessing')
        ) {
            mkdir(UtilityComponent::getTempDirectory().'/remoteprocessing');
        }

        /** @var RandomComponent $randomComponent */
        $randomComponent = MidasLoader::loadComponent('Random');
        $destination = UtilityComponent::getTempDirectory().'/remoteprocessing/'.$randomComponent->generateInt();
        while (file_exists($destination)) {
            $destination = UtilityComponent::getTempDirectory().'/remoteprocessing/'.$randomComponent->generateInt();
        }
        mkdir($destination);

        if (!$testingmode) {
            move_uploaded_file($_FILES['file']['tmp_name'], $destination.'/results.zip');
        }

        if ($testingmode) {
            return array();
        }

        if (file_exists($destination.'/results.zip')) {
            mkdir($destination.'/content');
            $target_directory = $destination.'/content';
            $filter = new Zend_Filter_Decompress(
                array('adapter' => 'Zip', 'options' => array('target' => $target_directory))
            );
            $compressed = $filter->filter($destination.'/results.zip');
            if ($compressed && file_exists($target_directory.'/parameters.txt')
            ) {
                $info = file_get_contents($target_directory.'/parameters.txt');
                $info = JsonComponent::decode($info);
                $job_id = $info['job_id'];

                /** @var Remoteprocessing_JobModel $jobModel */
                $jobModel = MidasLoader::loadModel('Job', 'remoteprocessing');
                $jobDao = $jobModel->load($job_id);
                $jobDao->setStatus(MIDAS_REMOTEPROCESSING_STATUS_DONE);
                $jobModel->save($jobDao);
                $info['pathResults'] = $destination.'/content';
                $info['log'] = file_get_contents($target_directory.'/log.txt');
                $info['userKey'] = $userDao->getKey();
                Zend_Registry::get('notifier')->callback($info['resultCallback'], $info);
            } else {
                throw new Exception('Error, unable to unzip results.', MIDAS_INVALID_PARAMETER);
            }
        } else {
            throw new Exception('Error, unable to find results.', MIDAS_INVALID_PARAMETER);
        }
        $this->_rrmdir($destination);

        return array();
    }

    /**
     * Recursively delete a folder.
     *
     * @param string $dir
     */
    private function _rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
        }

        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (filetype($dir.'/'.$object) == 'dir') {
                    $this->_rrmdir($dir.'/'.$object);
                } else {
                    unlink($dir.'/'.$object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}
