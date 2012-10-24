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
/** Component for api methods */
class Dicomuploader_ApiComponent extends AppComponent
{

  /**
   * Helper function for verifying keys in an input array
   */
  private function _validateParams($values, $keys)
    {
    foreach($keys as $key)
      {
      if(!array_key_exists($key, $values))
        {
        throw new Exception('Parameter '.$key.' must be set.', -1);
        }
      }
    }

  /**
   * Start DICOM file uploader
   * @param email (Optional) The user email to login
   * @param apikey (Optional) The user apikey to login
   * @param dcm2xml_cmd (Optional) The command to run dcm2xml
   * @param storescp_cmd (Optional) The command to run storescp
   * @param storescp_port (Optional) The TCP/IP port that storescp listens to
   * @param storescp_timeout(Optional)
   *   study timeout (seconds) storescp uses as '--eostudy-timeout' argument
   * @param incoming_dir(Optional)
   *   The incoming directory to receive and process DICOM files
   * @param dest_folder(Optional) pydas upload destination folder
   * @return
   */
  function start($args)
  {
    // Only administrator can call this api
    $midas_path = Zend_Registry::get('webroot');
    $midas_url = 'http://' . $_SERVER['HTTP_HOST'] . $midas_path;
    $userDao = Zend_Registry::get('userSession')->Dao;
    if(!$userDao || !$userDao->isAdmin())
      {
      throw new Exception('Only administrator can start DICOM uploader!', MIDAS_INVALID_POLICY);
      }
    // check if it is already running
    $status_args = array();
    if(!empty($args['storescp_cmd']))
      {
      $status_args['storescp_cmd'] = $args['storescp_cmd'];
      }
    $running_status = $this->status($status_args);
    if($running_status['status'] == MIDAS_DICOM_UPLOADER_IS_RUNNING)
      {
      throw new Exception('DICOM uploader is already running, please stop it first before use this api to start it again!', MIDAS_INVALID_POLICY);
      }
    // Get login information
    $user_email = '';
    $api_key = '';
    if(!empty($args['email']) && !empty($args['apikey']))
      {
      $user_email = $args['email'];
      $api_key = $args['apikey'];
      }
    else
      {
      $user_email = $userDao->getEmail();
      $userApiModel = MidasLoader::loadModel('Userapi', 'api');
      $userApiDao = $userApiModel->getByAppAndUser('Default', $userDao);
      if(!$userApiDao)
        {
        throw new Zend_Exception('You need to create a web-api key for this user for application: Default');
        }
      $api_key = $userApiDao->getApikey();
      }
    // Seet default values of optional parameters
    $dest_folder = 'Public';
    if(!empty($args['dest_folder']))
      {
      $dest_folder = $args['dest_folder'];
      }
    $dcm2xml_cmd = 'dcm2xml';
    if(!empty($args['dcm2xml_cmd']))
      {
      $dcm2xml_cmd = $args['dcm2xml_cmd'];
      }
    $storescp_cmd = 'storescp';
    if(!empty($args['storescp_cmd']))
      {
      $storescp_cmd = $args['storescp_cmd'];
      }
    $storescp_port = '55555';
    if(!empty($args['storescp_port']))
      {
      $$storescp_port = $args['storescp_port'];
      }
    $storescp_timeout = '15';
    if(!empty($args['storescp_timeout']))
      {
      $storescp_timeout = $args['storescp_timeout'];
      }
    $incoming_dir = '';
    if(!empty($args['incoming_dir']))
      {
      $incoming_dir = $args['incoming_dir'];
      }
    else
      {
      $uploaderComponent = MidasLoader::loadComponent('Uploader', 'dicomuploader');
      $incoming_dir = $uploaderComponent->getDefaultReceptionDir();
      }

    $python_cmd = 'python';
    $script_path = BASE_PATH .'/modules/dicomuploader/library/uploader.py';
    $storescp_params = array();
    $storescp_params[] = BASE_PATH .'/modules/dicomuploader/library/uploaderWrapper.py';
    $storescp_params[] = '--start';
    $storescp_params[] = '-s ' . $storescp_cmd;
    $storescp_params[] = '-p ' . $storescp_port;
    $storescp_params[] = '-t ' . $storescp_timeout;
    $storescp_params[] = '-i ' . $incoming_dir;
    $storescp_params[] = '-k ' . $script_path;
    $storescp_params[] = '-c ' . $dcm2xml_cmd;
    $storescp_params[] = '-u ' . $midas_url;
    $storescp_params[] = '-e ' . $user_email;
    $storescp_params[] = '-a ' . $api_key;
    $storescp_params[] = '-d ' . $dest_folder;
    $storescp_command = KWUtils::prepareExeccommand($python_cmd, $storescp_params);
    KWUtils::exec($storescp_command, $ouptut, '', $returnVal);
    $ret = array();
    $ret['message'] = 'Execution of storescp start script succeeded!';
    if($returnVal)
      {
      $ret['message'] = 'Execution of storescp start script failed!';
      }

    return $ret;
  }

  /**
   * Check DICOM file uploader status
   * @param storescp_cmd (Optional) The command to run storescp
   * @return
   */
  function status($args)
    {
    // Only administrator can call this api
    $userDao = Zend_Registry::get('userSession')->Dao;
    if(!$userDao || !$userDao->isAdmin())
      {
      throw new Exception('Only administrator can check the running status of DICOM uploader!', MIDAS_INVALID_POLICY);
      }
    $storescp_cmd = 'storescp';
    if(!empty($args['storescp_cmd']))
      {
      $storescp_cmd = $args['storescp_cmd'];
      }

    $ret = array();
    $ret['status'] = MIDAS_DICOM_UPLOADER_NOT_RUNNING;
    $ps_cmd = 'ps';
    $cmd_params = array();
    $cmd_params[] = 'ax';
    $ps_command = KWUtils::prepareExeccommand($ps_cmd, $cmd_params);
    KWUtils::exec($ps_command, $output);
    foreach ($output as $line)
      {
      $fields = preg_split("/\s+/", trim($line), 7);
      $process = $fields[4];
      if(!strcmp($process, $storescp_cmd))
        {
        $ret['status'] = MIDAS_DICOM_UPLOADER_IS_RUNNING;
        break;
        }
      }

    return $ret;
    }

 /**
   * Stop DICOM file uploader
   * @return
   */
  function stop($args)
  {
    // Only administrator can call this api
    $userDao = Zend_Registry::get('userSession')->Dao;
    if(!$userDao || !$userDao->isAdmin())
      {
      throw new Exception('Only administrator can stop DICOM uploader', MIDAS_INVALID_POLICY);
      }
    $python_cmd = 'python';
    $storescp_params = array();
    $storescp_params[] = BASE_PATH .'/modules/dicomuploader/library/uploaderWrapper.py';
    $storescp_params[] = '--stop';
    $storescp_command = KWUtils::prepareExeccommand($python_cmd, $storescp_params);
    KWUtils::exec($storescp_command, $ouptut, '', $returnVal);
    $ret = array();
    $ret['message'] = 'Execution of storescp stop script succeeded!';
    if($returnVal)
      {
      $ret['message'] = 'Execution of storescp stop script failed!';
      }

    return $ret;
  }
}

?>
