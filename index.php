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

if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
    if (function_exists('apache_get_modules')) {
        $mod_rewrite = in_array('mod_rewrite', apache_get_modules());
    } else {
        $mod_rewrite = getenv('HTTP_MOD_REWRITE') == 'On' ? true : false;
    }

    if (!$mod_rewrite) {
        echo 'Please install/enable the Apache rewrite module';
        exit();
    }
}

define('BASE_PATH', realpath(dirname(__FILE__)));
require_once BASE_PATH.'/vendor/autoload.php';
require_once BASE_PATH.'/core/include.php';

$application = new Zend_Application('global', CORE_CONFIG);
$application->bootstrap()->run();

if (array_key_exists('profiler', $_GET)) {
    Zend_Registry::get('logger')->info('Profiler for '.$_SERVER['REQUEST_URI']);
    $db = Zend_Db_Table::getDefaultAdapter();
    $profiler = $db->getProfiler();
    $profile = '';
    $queryCounts = array();
    $queryTimes = array();
    foreach ($profiler->getQueryProfiles() as $query) {
        if (array_key_exists($query->getQuery(), $queryCounts)) {
            $queryCounts[$query->getQuery()] += 1;
        } else {
            $queryCounts[$query->getQuery()] = 1;
        }
        $queryTimes[] = array('query' => $query->getQuery(), 'time_seconds' => $query->getElapsedSecs());
    }
    // Sort queries by count and time
    arsort($queryCounts, SORT_NUMERIC);
    function cmp($a, $b)
    {
        if ($a['time_seconds'] == $b['time_seconds']) {
            return 0;
        }

        return ($a['time_seconds'] < $b['time_seconds']) ? 1 : -1;
    }
    uasort($queryTimes, 'cmp');

    if (array_key_exists('profilerFiveSlowest', $_GET)) {
        // Logs much less info, only the five slowest queries.
        $fiveSlowest = array();
        $count = 0;
        foreach ($queryTimes as $query => $time) {
            $fiveSlowest[$query] = $time;
            if ($count++ == 4) {
                break;
            }
        }
        Zend_Registry::get('logger')->info(print_r($fiveSlowest, true));
    } else {
        Zend_Registry::get('logger')->info(print_r($queryCounts, true));
        Zend_Registry::get('logger')->info(print_r($queryTimes, true));
    }
}
