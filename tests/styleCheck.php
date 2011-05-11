<?php
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
