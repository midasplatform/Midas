<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
include_once BASE_PATH . '/library/KWUtils.php';
/** Uploade dicom files */
class Dicomserver_ServerComponent extends AppComponent
{

  /**
   * Verify that DICOM server is setup properly
   */
  public function isDICOMServerWorking()
  {
    $ret = array();
    $modulesConfig = Zend_Registry::get('configsModules');
    $dcm2xmlCommand = $modulesConfig['dicomserver']->dcm2xml;
    $storescpCommand = $modulesConfig['dicomserver']->storescp;
    $dcmqrscpCommand = $modulesConfig['dicomserver']->dcmqrscp;
    $dcmqridxCommand = $modulesConfig['dicomserver']->dcmqridx;
    $kwdicomextractorComponent = MidasLoader::loadComponent('Extractor', 'dicomextractor');
    $ret['dcm2xml'] = $kwdicomextractorComponent->getApplicationStatus($dcm2xmlCommand, 'dcm2xml');
    $ret['storescp'] = $kwdicomextractorComponent->getApplicationStatus($storescpCommand,
                                                   'storescp');
    $ret['dcmqrscp'] = $kwdicomextractorComponent->getApplicationStatus($dcmqrscpCommand,
                                                   'dcmqrscp');
    $ret['dcmqridx'] = $kwdicomextractorComponent->getApplicationStatus($dcmqridxCommand,
                                                   'dcmqridx');
    $receptionDir= $modulesConfig['dicomserver']->receptiondir;
    if (empty($receptionDir))
      {
      $receptionDir = $this->getDefaultReceptionDir();
      }
    $ret['Reception Directory Writable'] = array(is_writable($receptionDir));
    $peer_aes = $modulesConfig['dicomserver']->peer_aes;
    if (!empty($peer_aes) && strpos($peer_aes, '(') !== FALSE && strpos($peer_aes, ')') !== FALSE)
      {
      $ret['Peer AE List Not Empty'] = array(true, "At least one peer AE is given");
      }
    else
      {
      $ret['Peer AE List Not Empty'] = array(false, "Please input your peer AEs!");
      }
    $apiComponent = MidasLoader::loadComponent('Api', 'dicomserver');
    $status_args['storescp_cmd']= 'storescp';
    $status_results = $apiComponent->status($status_args);
    if ($status_results['status'] == MIDAS_DICOM_STORESCP_IS_RUNNING + MIDAS_DICOM_DCMQRSCP_IS_RUNNING)
      {
      $ret['Status'] = array(true, "DICOM Server is running");
      }
    else if ($status_results['status'] == MIDAS_DICOM_STORESCP_IS_RUNNING)
      {
      $ret['Status'] = array(false, 'DICOM C-STORE receiver is running, but DICOM Query/Retrieve service are NOT running');
      }
    else if ($status_results['status'] == MIDAS_DICOM_DCMQRSCP_IS_RUNNING)
      {
      $ret['Status'] = array(false, 'DICOM Query/Retrieve services are running, but DICOM C-STORE receiver is NOT running');
      }
    else if ($status_results['status'] == MIDAS_DICOM_SERVER_NOT_RUNNING)
      {
      $ret['Status'] = array(false, "DICOM Server is not running");
      }
    else // MIDAS_DICOM_SERVER_NOT_SUPPORTED
      {
      $ret['Status'] = array(false, 'This module is currently not supported in Windows.');
      }

    return $ret;
  }

  /**
   * Get default reception directory
   */
  public function getDefaultReceptionDir()
  {
    $utilityComponent = MidasLoader::loadComponent('Utility');
    $default_reception_dir = $utilityComponent->getTempDirectory('');
    if(substr($default_reception_dir, -1) == '/')
      {
      $default_reception_dir = substr($default_reception_dir, 0, -1);
      }
    $default_reception_dir .= 'dicomserver';
    if(!file_exists($default_reception_dir) && !KWUtils::mkDir($default_reception_dir, 0777))
      {
      throw new Zend_Exception("couldn't create dir ".$default_reception_dir);
      }

    return $default_reception_dir;
  }

  /**
   * Generate the configuaration file used for dcmqrscp
   */
  public function generateDcmqrscpConfig()
    {
    $modulesConfig = Zend_Registry::get('configsModules');
    $pacs_dir = $modulesConfig['dicomserver']->receptiondir . PACS_DIR;
    $cfg_file = $pacs_dir . DCMQRSCP_CFG_FILE;
    $cfg_file_content = "NetworkTCPPort  = " . $modulesConfig['dicomserver']->dcmqrscp_port . "\n";
    $cfg_file_content .= "MaxPDUSize      = 16384\n";
    $cfg_file_content .= "MaxAssociations = 16\n\n";
    $cfg_file_content .= "HostTable BEGIN\n";
    $peer_aes = $modulesConfig['dicomserver']->peer_aes;
    $peer_aes_arr = explode(";", $peer_aes);
    $symblic_name_arr = array();
    foreach($peer_aes_arr as $index => $peer_ae)
      {
      $cfg_file_content .= "ae" . $index . "       = " . $peer_ae . "\n";
      $symblic_name_arr[] = "ae" . $index;
      }
    $cfg_file_content .= "AES = " . implode(",", $symblic_name_arr) . "\n";
    $cfg_file_content .= "HostTable END\n\n";
    $cfg_file_content .= "VendorTable BEGIN\n";
    $cfg_file_content .= "VendorTable END\n\n";
    $cfg_file_content .= "AETable BEGIN\n";
    $cfg_file_content .= $modulesConfig['dicomserver']->server_ae_title . "    " .  $pacs_dir . "    R  (200, 1024mb) AES\n";
    $cfg_file_content .= "AETable END\n";
    file_put_contents($cfg_file, $cfg_file_content);
    }


  /**
   * Register DICOM image files (bistreams)
   */
  public function register($revision)
    {
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) < 1)
      {
      return;
      }
    $modulesConfig = Zend_Registry::get('configsModules');
    $command = $modulesConfig['dicomserver']->dcmqridx;
    $command = str_replace("'", '',$command);
    $command_params = array();
    $aeStorage = $modulesConfig['dicomserver']->receptiondir . PACS_DIR;
    $aeStorage = str_replace("'", '', $aeStorage);
    $command_params[] = $aeStorage;
    foreach($bitstreams as $bitstream)
      {
      $command_params[] = $bitstream->getFullPath();
      $regisgterCommand = KWUtils::prepareExeccommand($command, $command_params);
      array_pop($command_params); // prepare for next iteration in the loop
      KWUtils::exec($regisgterCommand, $output, '', $returnVal);
      if($returnVal)
        {
        $exception_string = "Failed to register DICOM images! \n Reason:" . implode("\n", $output);
        throw new Zend_Exception(htmlspecialchars($exception_string, ENT_QUOTES), MIDAS_INVALID_POLICY);
        }
      }
    }

} // end class
