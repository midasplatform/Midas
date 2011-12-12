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
define("TEST_DIR", dirname(__FILE__));

$_SERVER['argv'][0] = 'run.php';
$_SERVER['argv'][1] = '--src';
$toTest='';

$toTest.=TEST_DIR.'/../core/AppComponent.php,';
$toTest.=TEST_DIR.'/../core/AppController.php,';
$toTest.=TEST_DIR.'/../core/AppComponent.php,';
$toTest.=TEST_DIR.'/../core/AppForm.php,';
$toTest.=TEST_DIR.'/../core/Bootstrap.php,';
$toTest.=TEST_DIR.'/../core/include.php,';
$toTest.=TEST_DIR.'/../index.php,';
$toTest.=TEST_DIR.'/../core/models,';
$toTest.=TEST_DIR.'/../library/MIDAS,';
$toTest.=TEST_DIR.'/../core/controllers';
$_SERVER['argv'][2] = $toTest;
$_SERVER['argv'][3] = '--outdir';
$_SERVER['argv'][4] = TEST_DIR.'/log/style-report';
$_SERVER["argc"] = 5;

include 'library/PhpCheckstyle/run.php';
