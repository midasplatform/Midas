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
class Dicomserver_ApiComponent extends AppComponent
  {
  /** Return the user dao */
  private function _callModuleApiMethod($args, $coreApiMethod, $resource = null, $hasReturn = true)
    {
    $ApiComponent = MidasLoader::loadComponent('Api'.$resource, 'dicomserver');
    $rtn = $ApiComponent->$coreApiMethod($args);
    if($hasReturn)
      {
      return $rtn;
      }
    }

  /**
   * Start DICOM server
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
   * @return
   */
  function start($args)
    {
    return $this->_callModuleApiMethod($args, 'start', 'server');
    }

  /**
   * Check DICOM server status
   * @param storescp_cmd (Optional) The command to run storescp
   * @param dcmqrscp_cmd (Optional) The command to run dcmqrscp
   * @return array('status' => string)
   */
  function status($args)
    {
    return $this->_callModuleApiMethod($args, 'status', 'server');
    }

 /**
   * Stop DICOM server
   * @param storescp_cmd (Optional) The command to run storescp
   * @param dcmqrscp_cmd (Optional) The command to run dcmqrscp
   * @param incoming_dir (Optional) The incoming directory to receive and process DICOM files
   * @param get_command (Optional) If set, will not stop DICOM server, but only get command used to stop DICOM server in command line.
   * @return
   */
  function stop($args)
    {
    return $this->_callModuleApiMethod($args, 'stop', 'server');
    }

  /**
   * Register DICOM images from a revision to let them be available for DICOM query/retrieve services.
   * @param item the id of the item to be registered
   * @return the revision dao (latest revision of the item) that was registered
   */
  function register($args)
    {
    return $this->_callModuleApiMethod($args, 'register', 'server');
    }

  /**
   * Check if the DICOM images in the item was registered and can be accessed by DICOM query/retrieve services.
   * @param item the id of the item to be checked
   * @return array('status' => bool)
   */
  function registrationStatus($args)
    {
    return $this->_callModuleApiMethod($args, 'registrationStatus', 'server');
    }
  }
