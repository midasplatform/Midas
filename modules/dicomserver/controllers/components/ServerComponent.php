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

/** Upload dicom files */
class Dicomserver_ServerComponent extends AppComponent
{
    /** @var string */
    public $moduleName = 'dicomserver';

    /**
     * Verify that DICOM server is setup properly
     */
    public function isDICOMServerWorking()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $dcm2xmlCommand = $settingModel->getValueByName(MIDAS_DICOMSERVER_DCM2XML_COMMAND_KEY, $this->moduleName);
        $storescpCommand = $settingModel->getValueByName(MIDAS_DICOMSERVER_STORESCP_COMMAND_KEY, $this->moduleName);
        $dcmqrscpCommand = $settingModel->getValueByName(MIDAS_DICOMSERVER_DCMQRSCP_COMMAND_KEY, $this->moduleName);
        $dcmqridxCommand = $settingModel->getValueByName(MIDAS_DICOMSERVER_DCMQRIDX_COMMAND_KEY, $this->moduleName);

        $kwdicomextractorComponent = MidasLoader::loadComponent('Extractor', 'dicomextractor');
        $ret = array();
        $ret['dcm2xml'] = $kwdicomextractorComponent->getApplicationStatus($dcm2xmlCommand, 'dcm2xml');
        $ret['storescp'] = $kwdicomextractorComponent->getApplicationStatus($storescpCommand, 'storescp');
        $ret['dcmqrscp'] = $kwdicomextractorComponent->getApplicationStatus($dcmqrscpCommand, 'dcmqrscp');
        $ret['dcmqridx'] = $kwdicomextractorComponent->getApplicationStatus($dcmqridxCommand, 'dcmqridx');

        $receptionDir =  $settingModel->getValueByName(MIDAS_DICOMSERVER_RECEPTION_DIRECTORY_KEY, $this->moduleName);

        if (empty($receptionDir)) {
            $receptionDir = $this->getDefaultReceptionDir();
        }
        $ret['Reception Directory Writable'] = array(is_writable($receptionDir));

        $peerAes = $settingModel->getValueByName(MIDAS_DICOMSERVER_PEER_AES_KEY, $this->moduleName);

        if (!empty($peerAes) && strpos($peerAes, '(') !== false && strpos($peerAes, ')') !== false
        ) {
            $ret['Peer AE List Not Empty'] = array(true, "At least one peer AE is given");
        } else {
            $ret['Peer AE List Not Empty'] = array(false, "Please input your peer AEs!");
        }

        /** @var Dicomserver_ApiComponent $apiComponent */
        $apiComponent = MidasLoader::loadComponent('Api', 'dicomserver');
        $status_args['storescp_cmd'] = $storescpCommand;
        $status_args['dcmqrscp_cmd'] = $dcmqrscpCommand;
        $status_results = $apiComponent->status($status_args);
        if ($status_results['status'] == MIDAS_DICOMSERVER_STORESCP_IS_RUNNING + MIDAS_DICOMSERVER_DCMQRSCP_IS_RUNNING) {
            $ret['Status'] = array(true, "DICOM Server is running");
        } elseif ($status_results['status'] == MIDAS_DICOMSERVER_STORESCP_IS_RUNNING) {
            $ret['Status'] = array(
                false,
                'DICOM C-STORE receiver is running, but DICOM Query/Retrieve service are NOT running',
            );
        } elseif ($status_results['status'] == MIDAS_DICOMSERVER_DCMQRSCP_IS_RUNNING) {
            $ret['Status'] = array(
                false,
                'DICOM Query/Retrieve services are running, but DICOM C-STORE receiver is NOT running',
            );
        } elseif ($status_results['status'] == MIDAS_DICOMSERVER_SERVER_NOT_RUNNING) {
            $ret['Status'] = array(false, "DICOM Server is not running");
        } else { // MIDAS_DICOMSERVER_SERVER_NOT_SUPPORTED
            $ret['Status'] = array(false, 'This module is currently not supported in Windows.');
        }

        return $ret;
    }

    /**
     * Get default reception directory
     */
    public function getDefaultReceptionDir()
    {
        /** @var UtilityComponent $utilityComponent */
        $utilityComponent = MidasLoader::loadComponent('Utility');
        $defaultReceptionDirectory = $utilityComponent->getTempDirectory('dicomserver');

        return $defaultReceptionDirectory;
    }

    /**
     * Generate the configuration file used for dcmqrscp
     */
    public function generateDcmqrscpConfig()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $receptionDirectory = $settingModel->getValueByName(MIDAS_DICOMSERVER_RECEPTION_DIRECTORY_KEY, $this->moduleName);
        $dcmqrscpPort = $settingModel->getValueByName(MIDAS_DICOMSERVER_DCMQRSCP_PORT_KEY, $this->moduleName);
        $pacsDirectory = $receptionDirectory.MIDAS_DICOMSERVER_PACS_DIRECTORY;
        $cfgFile = $pacsDirectory.MIDAS_DICOMSERVER_DCMQRSCP_CFG_FILE;
        $cfgFileContent = "NetworkTCPPort  = ".$dcmqrscpPort."\n";
        $cfgFileContent .= "MaxPDUSize      = 16384\n";
        $cfgFileContent .= "MaxAssociations = 16\n\n";
        $cfgFileContent .= "HostTable BEGIN\n";
        $peerAes = $settingModel->getValueByName(MIDAS_DICOMSERVER_PEER_AES_KEY, $this->moduleName);
        $peerAesArray = explode(";", $peerAes);
        $symbolicNameArray = array();
        foreach ($peerAesArray as $index => $peer_ae) {
            $cfgFileContent .= "ae".$index."       = ".$peer_ae."\n";
            $symbolicNameArray[] = "ae".$index;
        }
        $serverAeTitle = $settingModel->getValueByName(MIDAS_DICOMSERVER_SERVER_AE_TITLE_KEY, $this->moduleName);
        $cfgFileContent .= "AES = ".implode(",", $symbolicNameArray)."\n";
        $cfgFileContent .= "HostTable END\n\n";
        $cfgFileContent .= "VendorTable BEGIN\n";
        $cfgFileContent .= "VendorTable END\n\n";
        $cfgFileContent .= "AETable BEGIN\n";
        $cfgFileContent .= $serverAeTitle."    ".$pacsDirectory."    R  (200, 1024mb) AES\n";
        $cfgFileContent .= "AETable END\n";
        file_put_contents($cfgFile, $cfgFileContent);
    }

    /**
     * Register DICOM image files (bitstreams)
     */
    public function register($revision)
    {
        $bitstreams = $revision->getBitstreams();
        if (count($bitstreams) < 1) {
            return;
        }

        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $command = $settingModel->getValueByName(MIDAS_DICOMSERVER_DCMQRIDX_COMMAND_KEY, $this->moduleName);
        $command = str_replace("'", '', $command);
        $commandParams = array();
        $receptionDirectory = $settingModel->getValueByName(MIDAS_DICOMSERVER_RECEPTION_DIRECTORY_KEY, $this->moduleName);
        if (!is_writable($receptionDirectory)) {
            throw new Zend_Exception(
                "Please configure Dicom Server module correctly. Its reception directory is NOT writable!",
                MIDAS_INVALID_POLICY
            );
        }
        $aeStorage = $receptionDirectory.MIDAS_DICOMSERVER_PACS_DIRECTORY;
        $aeStorage = str_replace("'", '', $aeStorage);
        $commandParams[] = $aeStorage;
        foreach ($bitstreams as $bitstream) {
            $commandParams[] = $bitstream->getFullPath();
            $registerCommand = KWUtils::prepareExeccommand($command, $commandParams);
            array_pop($command_params); // prepare for next iteration in the loop
            KWUtils::exec($registerCommand, $output, '', $returnVal);
            if ($returnVal) {
                $exceptionString = "Failed to register DICOM images! \n Reason:".implode("\n", $output);
                throw new Zend_Exception(htmlspecialchars($exceptionString, ENT_QUOTES), MIDAS_INVALID_POLICY);
            }
        }

        /** @var Dicomserver_RegistrationModel $registrationModel */
        $registrationModel = MidasLoader::loadModel('Registration', 'dicomserver');
        $itemId = $revision->getItemId();
        if (!$registrationModel->checkByItemId($itemId)) {
            $registrationModel->createRegistration($itemId);
        }
    }
}
