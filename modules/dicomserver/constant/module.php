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

define('MIDAS_DICOMSERVER_DCM2XML_COMMAND_KEY', 'dcm2xml_command');
define('MIDAS_DICOMSERVER_DCM2XML_COMMAND_DEFAULT_VALUE', 'dcm2xml');
define('MIDAS_DICOMSERVER_STORESCP_COMMAND_KEY', 'storescp_command');
define('MIDAS_DICOMSERVER_STORESCP_COMMAND_DEFAULT_VALUE', 'storescp');
define('MIDAS_DICOMSERVER_STORESCP_PORT_KEY', 'storescp_port');
define('MIDAS_DICOMSERVER_STORESCP_PORT_DEFAULT_VALUE', 55555);
define('MIDAS_DICOMSERVER_STORESCP_STUDY_TIMEOUT_KEY', 'storescp_study_timeout');
define('MIDAS_DICOMSERVER_STORESCP_STUDY_TIMEOUT_DEFAULT_VALUE', 15);
define('MIDAS_DICOMSERVER_RECEPTION_DIRECTORY_KEY', 'reception_directory');
define('MIDAS_DICOMSERVER_RECEPTION_DIRECTORY_DEFAULT_VALUE', '');
define('MIDAS_DICOMSERVER_DESTINATION_FOLDER_KEY', 'destination_folder');
define('MIDAS_DICOMSERVER_DESTINATION_FOLDER_DEFAULT_VALUE', 'Public');
define('MIDAS_DICOMSERVER_DCMQRSCP_COMMAND_KEY', 'dcmqrscp_command');
define('MIDAS_DICOMSERVER_DCMQRSCP_COMMAND_DEFAULT_VALUE', 'dcmqrscp');
define('MIDAS_DICOMSERVER_DCMQRSCP_PORT_KEY', 'dcmqrscp_port');
define('MIDAS_DICOMSERVER_DCMQRSCP_PORT_DEFAULT_VALUE', 9885);
define('MIDAS_DICOMSERVER_DCMQRIDX_COMMAND_KEY', 'dcmqridx_command');
define('MIDAS_DICOMSERVER_DCMQRIDX_COMMAND_DEFAULT_VALUE', 'dcmqridx');
define('MIDAS_DICOMSERVER_SERVER_AE_TITLE_KEY', 'server_ae_title');
define('MIDAS_DICOMSERVER_SERVER_AE_TITLE_DEFAULT_VALUE', 'MIDAS_PACS');
define('MIDAS_DICOMSERVER_PEER_AES_KEY', 'peer_aes');
define('MIDAS_DICOMSERVER_PEER_AES_DEFAULT_VALUE', '');

// server status
define('MIDAS_DICOMSERVER_STORESCP_IS_RUNNING', 1);
define('MIDAS_DICOMSERVER_DCMQRSCP_IS_RUNNING', 2);
define('MIDAS_DICOMSERVER_SERVER_NOT_RUNNING', 0);
define('MIDAS_DICOMSERVER_SERVER_NOT_SUPPORTED', -1);

// default subdirectories and files
define('MIDAS_DICOMSERVER_LOGS_DIRECTORY', '/logs');
define('MIDAS_DICOMSERVER_PROCESSING_DIRECTORY', '/processing');
define('MIDAS_DICOMSERVER_PACS_DIRECTORY', '/pacs');
define('MIDAS_DICOMSERVER_DCMQRSCP_CFG_FILE', '/dcmqrscp_midas.cfg');
