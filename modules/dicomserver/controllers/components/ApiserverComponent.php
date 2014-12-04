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

include_once BASE_PATH.'/library/KWUtils.php';

/** Component for api methods */
class Dicomserver_ApiserverComponent extends AppComponent
{
    /**
     * Start DICOM server
     *
     * @path /dicomserver/server/start
     * @http POST
     * @param email (Optional) The user email to login
     * @param apikey (Optional) The user apikey to login
     * @param dcm2xml_cmd (Optional) The command to run dcm2xml
     * @param storescp_cmd (Optional) The command to run storescp
     * @param storescp_port (Optional) The TCP/IP port that storescp listens to
     * @param storescp_timeout (Optional) Study timeout (seconds) storescp uses as '--eostudy-timeout' argument
     * @param incoming_dir (Optional) The incoming directory to receive and process DICOM files
     * @param dest_folder (Optional) Pydas upload destination folder
     * @param dcmqrscp_cmd (Optional) The command to run dcmqrscp
     * @param get_command (Optional) If set, will not start DICOM server, but only get command used to start DICOM server in command line.
     * @return array
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function start($args)
    {
        // Only administrator can call this api
        $midas_path = Zend_Registry::get('webroot');
        $midas_url = 'http://'.$_SERVER['HTTP_HOST'].$midas_path;
        $userDao = Zend_Registry::get('userSession')->Dao;
        if (!$userDao || !$userDao->isAdmin()) {
            throw new Exception('Only administrator can start DICOM server!', MIDAS_INVALID_POLICY);
        }
        // check if it is already running
        $status_args = array();
        if (!empty($args['storescp_cmd'])) {
            $status_args['storescp_cmd'] = $args['storescp_cmd'];
        }
        if (!empty($args['dcmqrscp_cmd'])) {
            $status_args['dcmqrscp_cmd'] = $args['dcmqrscp_cmd'];
        }
        $running_status = $this->status($status_args);
        if ($running_status['status'] > MIDAS_DICOMSERVER_SERVER_NOT_RUNNING && !array_key_exists('get_command', $args)
        ) {
            throw new Exception(
                'At least one DICOM service is already running. Please stop all services first before start them again!',
                MIDAS_INVALID_POLICY
            );
        }
        // Get login information
        if (!empty($args['email']) && !empty($args['apikey'])) {
            $user_email = $args['email'];
            $api_key = $args['apikey'];
        } else {
            $user_email = $userDao->getEmail();
            /** @var UserapiModel $userApiModel */
            $userApiModel = MidasLoader::loadModel('Userapi');
            $userApiDao = $userApiModel->getByAppAndUser('Default', $userDao);
            if (!$userApiDao) {
                throw new Exception('You need to create a web API key for this user for application: Default');
            }
            $api_key = $userApiDao->getApikey();
        }
        // Set default values of optional parameters
        $dest_folder = 'Public';
        if (!empty($args['dest_folder'])) {
            $dest_folder = $args['dest_folder'];
        }
        $dcm2xml_cmd = 'dcm2xml';
        if (!empty($args['dcm2xml_cmd'])) {
            $dcm2xml_cmd = $args['dcm2xml_cmd'];
        }
        $storescp_cmd = 'storescp';
        if (!empty($args['storescp_cmd'])) {
            $storescp_cmd = $args['storescp_cmd'];
        }
        $storescp_port = '55555';
        if (!empty($args['storescp_port'])) {
            $$storescp_port = $args['storescp_port'];
        }
        $storescp_timeout = '15';
        if (!empty($args['storescp_timeout'])) {
            $storescp_timeout = $args['storescp_timeout'];
        }
        if (!empty($args['incoming_dir'])) {
            $incoming_dir = $args['incoming_dir'];
        } else {
            /** @var Dicomserver_ServerComponent $serverComponent */
            $serverComponent = MidasLoader::loadComponent('Server', 'dicomserver');
            $incoming_dir = $serverComponent->getDefaultReceptionDir();
        }
        $processing_dir = $incoming_dir.MIDAS_DICOMSERVER_PROCESSING_DIRECTORY;
        if (!file_exists($processing_dir)) {
            KWUtils::mkDir($processing_dir, 0777);
        }
        $log_dir = $incoming_dir.MIDAS_DICOMSERVER_LOGS_DIRECTORY;
        if (!file_exists($log_dir)) {
            KWUtils::mkDir($log_dir, 0777);
        }
        $dcmqrscp_cmd = 'dcmqrscp';
        if (!empty($args['dcmqrscp_cmd'])) {
            $dcmqrscp_cmd = $args['dcmqrscp_cmd'];
        }
        $dcmqrscp_pacs_dir = $incoming_dir.MIDAS_DICOMSERVER_PACS_DIRECTORY;
        if (!file_exists($dcmqrscp_pacs_dir)) {
            KWUtils::mkDir($dcmqrscp_pacs_dir, 0777);
        }
        // DICOM Store Service Receiver
        $python_cmd = 'python';
        $script_path = BASE_PATH.'/modules/dicomserver/library/server.py';
        $python_params = array();
        $python_params[] = BASE_PATH.'/modules/dicomserver/library/serverWrapper.py';
        $python_params[] = '--start';
        // used by storescp
        $python_params[] = '-s '.$storescp_cmd;
        $python_params[] = '-p '.$storescp_port;
        $python_params[] = '-t '.$storescp_timeout;
        $python_params[] = '-i '.$incoming_dir;
        $python_params[] = '-k '.$script_path;
        $python_params[] = '-c '.$dcm2xml_cmd;
        $python_params[] = '-u '.$midas_url;
        $python_params[] = '-e '.$user_email;
        $python_params[] = '-a '.$api_key;
        $python_params[] = '-d '.$dest_folder;
        // used by dcmqrscp
        $python_params[] = '-q '.$dcmqrscp_cmd;
        $python_params[] = '-f '.$dcmqrscp_pacs_dir.MIDAS_DICOMSERVER_DCMQRSCP_CFG_FILE;
        $start_server_command = KWUtils::prepareExeccommand($python_cmd, $python_params);
        if (array_key_exists('get_command', $args)) {
            $start_server_command_string = str_replace("'", "", $start_server_command);

            return escapeshellarg($start_server_command_string);
        }
        if (!isset($serverComponent)) {
            /** @var Dicomserver_ServerComponent $serverComponent */
            $serverComponent = MidasLoader::loadComponent('Server', 'dicomserver');
        }
        $returnVal = $serverComponent->generateDcmqrscpConfig();
        if ($returnVal) {
            $output = "Cannot generate the configuration file used by dcmqrscp. \n";
        } else {
            KWUtils::exec($start_server_command, $output, '', $returnVal);
        }
        if ($returnVal) {
            $exception_string = "Failed to start DICOM server! \n Reason:".implode("\n", $output);
            throw new Exception(htmlspecialchars($exception_string, ENT_QUOTES), MIDAS_INVALID_POLICY);
        }

        $ret = array();
        $ret['message'] = 'Succeeded to start DICOM C-STORE receiver and Query-Retrieve services!';

        return $ret;
    }

    /**
     * Check DICOM server status
     *
     * @path /dicomserver/server/status
     * @http GET
     * @param storescp_cmd (Optional) The command to run storescp
     * @param dcmqrscp_cmd (Optional) The command to run dcmqrscp
     * @return array('status' => string)
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function status($args)
    {
        // Only administrator can call this api
        $userDao = Zend_Registry::get('userSession')->Dao;
        if (!$userDao || !$userDao->isAdmin()) {
            throw new Exception(
                'Only administrator can check the running status of DICOM server!', MIDAS_INVALID_POLICY
            );
        }
        $storescp_cmd = 'storescp';
        if (!empty($args['storescp_cmd'])) {
            $storescp_cmd = $args['storescp_cmd'];
        }
        $dcmqrscp_cmd = 'dcmqrscp';
        if (!empty($args['dcmqrscp_cmd'])) {
            $dcmqrscp_cmd = $args['dcmqrscp_cmd'];
        }

        $ret = array();
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // currently not supported in windows
            $ret['status'] = MIDAS_DICOMSERVER_SERVER_NOT_SUPPORTED;

            return $ret;
        } else {
            $ret['status'] = MIDAS_DICOMSERVER_SERVER_NOT_RUNNING;
        }
        $ps_cmd = 'ps';
        $cmd_params = array();
        $cmd_params[] = 'ax';
        $ps_command = KWUtils::prepareExeccommand($ps_cmd, $cmd_params);
        KWUtils::exec($ps_command, $output);
        $runningSCPs = 0;
        $totalSCPs = 2;
        foreach ($output as $line) {
            $fields = preg_split("/\s+/", trim($line));
            $process = $fields[4];
            if (!strcmp($process, $storescp_cmd)) {
                $ret['status'] = MIDAS_DICOMSERVER_STORESCP_IS_RUNNING;
                // need to be updated if python script is changed
                $ret['user_email'] = $fields[21];
                $runningSCPs += 1;
                if ($runningSCPs == $totalSCPs) {
                    break;
                }
            } elseif (!strcmp($process, $dcmqrscp_cmd)) {
                $ret['status'] += MIDAS_DICOMSERVER_DCMQRSCP_IS_RUNNING;
                $runningSCPs += 1;
                if ($runningSCPs == $totalSCPs) {
                    break;
                }
            }
        }

        return $ret;
    }

    /**
     * Stop DICOM server
     *
     * @path /dicomserver/server/stop
     * @http POST
     * @param storescp_cmd (Optional) The command to run storescp
     * @param dcmqrscp_cmd (Optional) The command to run dcmqrscp
     * @param incoming_dir (Optional) The incoming directory to receive and process DICOM files
     * @param get_command (Optional) If set, will not stop DICOM server, but only get command used to stop DICOM server in command line.
     * @return
     */
    public function stop($args)
    {
        // Only administrator can call this api
        $userDao = Zend_Registry::get('userSession')->Dao;
        if (!$userDao || !$userDao->isAdmin()) {
            throw new Exception('Only administrator can stop DICOM server', MIDAS_INVALID_POLICY);
        }
        $ret = array();
        $status_args = array();
        if (!empty($args['storescp_cmd'])) {
            $status_args['storescp_cmd'] = $args['storescp_cmd'];
        }
        if (!empty($args['dcmqrscp_cmd'])) {
            $status_args['dcmqrscp_cmd'] = $args['dcmqrscp_cmd'];
        }

        $running_status = $this->status($status_args);
        if ($running_status['status'] == MIDAS_DICOMSERVER_SERVER_NOT_RUNNING && !array_key_exists('get_command', $args)
        ) {
            $ret['message'] = 'DICOM server is not running now!';

            return $ret;
        }

        $storescp_cmd = 'storescp';
        if (!empty($args['storescp_cmd'])) {
            $storescp_cmd = $args['storescp_cmd'];
        }
        $dcmqrscp_cmd = 'dcmqrscp';
        if (!empty($args['dcmqrscp_cmd'])) {
            $dcmqrscp_cmd = $args['dcmqrscp_cmd'];
        }
        if (!empty($args['incoming_dir'])) {
            $incoming_dir = $args['incoming_dir'];
        } else {
            /** @var Dicomserver_ServerComponent $serverComponent */
            $serverComponent = MidasLoader::loadComponent('Server', 'dicomserver');
            $incoming_dir = $serverComponent->getDefaultReceptionDir();
        }
        $log_dir = $incoming_dir.MIDAS_DICOMSERVER_LOGS_DIRECTORY;
        if (!file_exists($log_dir)) {
            KWUtils::mkDir($log_dir, 0777);
        }

        $python_cmd = 'python';
        $python_params = array();
        $python_params[] = BASE_PATH.'/modules/dicomserver/library/serverWrapper.py';
        $python_params[] = '--stop';
        $python_params[] = '-i '.$incoming_dir;
        $python_params[] = '-s '.$storescp_cmd;
        $python_params[] = '-q '.$dcmqrscp_cmd;
        $stop_server_command = KWUtils::prepareExeccommand($python_cmd, $python_params);
        if (array_key_exists('get_command', $args)) {
            $stop_server_command_string = str_replace("'", "", $stop_server_command);

            return escapeshellarg($stop_server_command_string);
        }
        KWUtils::exec($stop_server_command, $output, '', $returnVal);

        $ret['message'] = 'Succeeded to stop DICOM C-STORE receiver and Query-Retrieve services!';
        if ($returnVal) {
            $exception_string = "Failed to stop DICOM server! \n Reason:".implode("\n", $output);
            throw new Zend_Exception(htmlspecialchars($exception_string, ENT_QUOTES), MIDAS_INVALID_POLICY);
        }

        return $ret;
    }

    /**
     * Register DICOM images from a revision to let them be available for DICOM query/retrieve services.
     *
     * @path /dicomserver/server/register
     * @http POST
     * @param item the id of the item to be registered
     * @return the revision dao (latest revision of the item) that was registered
     */
    public function register($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('item'));

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel("Item");

        /** @var AuthenticationComponent $authComponent */
        $authComponent = MidasLoader::loadComponent('Authentication');
        $itemDao = $itemModel->load($args['item']);
        $userDao = $authComponent->getUser($args, Zend_Registry::get('userSession')->Dao);
        if (!$itemModel->policyCheck($itemDao, $userDao, MIDAS_POLICY_WRITE)) {
            throw new Exception(
                'You didn\'t log in or you don\'t have the write '.'permission for the given item.',
                MIDAS_INVALID_POLICY
            );
        }

        $revisionDao = $itemModel->getLastRevision($itemDao);

        /** @var Dicomserver_ServerComponent $dicomComponent */
        $dicomComponent = MidasLoader::loadComponent('Server', 'dicomserver');
        $dicomComponent->register($revisionDao);

        return $revisionDao->toArray();
    }

    /**
     * Check if the DICOM images in the item was registered and can be accessed by DICOM query/retrieve services.
     *
     * @path /dicomserver/server/registrationstatus
     * @http GET
     * @param item the id of the item to be checked
     * @return array('status' => bool)
     */
    public function registrationStatus($args)
    {
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('item'));

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel("Item");

        /** @var AuthenticationComponent $authComponent */
        $authComponent = MidasLoader::loadComponent('Authentication');
        $itemDao = $itemModel->load($args['item']);
        $userDao = $authComponent->getUser($args, Zend_Registry::get('userSession')->Dao);
        if (!$itemModel->policyCheck($itemDao, $userDao, MIDAS_POLICY_WRITE)) {
            throw new Exception(
                'You didn\'t log in or you don\'t have the write '.'permission for the given item.',
                MIDAS_INVALID_POLICY
            );
        }

        /** @var Dicomserver_RegistrationModel $registrationModel */
        $registrationModel = MidasLoader::loadModel('Registration', 'dicomserver');
        if (!$registrationModel->checkByItemId($args['item'])) {
            return array('status' => false);
        } else {
            return array('status' => true);
        }
    }
}
