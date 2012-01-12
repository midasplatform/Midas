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
define("MIDAS_REMOTEPROCESSING_OS_WINDOWS", 'windows');
define("MIDAS_REMOTEPROCESSING_OS_LINUX", 'linux');

define("MIDAS_REMOTEPROCESSING_STATUS_WAIT", 0);
define("MIDAS_REMOTEPROCESSING_STATUS_STARTED", 1);
define("MIDAS_REMOTEPROCESSING_STATUS_DONE", 2);

define("MIDAS_REMOTEPROCESSING_RELATION_TYPE_EXECUTABLE", 0);
define("MIDAS_REMOTEPROCESSING_RELATION_TYPE_INPUT", 1);
define("MIDAS_REMOTEPROCESSING_RELATION_TYPE_OUPUT", 2);
define("MIDAS_REMOTEPROCESSING_RELATION_TYPE_RESULTS", 3);
?>
