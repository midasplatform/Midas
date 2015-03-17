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

define('MIDAS_PVW_PVPYTHON_KEY', 'pvpython');
define('MIDAS_PVW_PVPYTHON_DEFAULT_VALUE', 'pvpython');
define('MIDAS_PVW_PORTS_KEY', 'ports');
define('MIDAS_PVW_PORTS_DEFAULT_VALUE', '9000,9001');
define('MIDAS_PVW_DISPLAY_ENV_KEY', 'displayEnv');
define('MIDAS_PVW_DISPLAY_ENV_DEFAULT_VALUE', '');

// Max amount of time in seconds to wait between starting pvpython and it binding to the TCP port.
// If this time is surpassed, we stop waiting and alert the user that something failed.
define('MIDAS_PVW_STARTUP_TIMEOUT', 5);
