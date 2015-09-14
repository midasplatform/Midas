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

/** Data management Component */
class DateComponent extends AppComponent
{
    /**
     * Format date (ex: 01/14/2011 or 14/01/2011 (fr or en).
     *
     * @param int|string $timestamp
     * @return string
     */
    public static function formatDate($timestamp)
    {
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
            if ($timestamp == false) {
                return '';
            }
        }

        if ((int) Zend_Registry::get('configGlobal')->get('internationalization', 0) === 1) {
            /** @var SettingModel $settingModel */
            $settingModel = MidasLoader::loadModel('Setting');

            if ($settingModel->getValueByNameWithDefault('language', 'en') === 'fr') {
                return date('d', $timestamp).'/'.date('m', $timestamp).'/'.date('Y', $timestamp);
            }
        }

        return date('m', $timestamp).'/'.date('d', $timestamp).'/'.date('Y', $timestamp);
    }

    /**
     * Format the date (ex: 5 days ago).
     *
     * @param int|string $timestamp
     * @param bool $onlyTime
     * @return string
     */
    public static function ago($timestamp, $onlyTime = false)
    {
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
            if ($timestamp == false) {
                return '';
            }
        }
        $difference = time() - $timestamp;
        $periods = array('second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'decade');
        $periodsFr = array('seconde', 'minute', 'heure', 'jour', 'semaine', 'mois', 'annee', 'decades');
        $lengths = array('60', '60', '24', '7', '4.35', '12', '10');
        for ($j = 0; $difference >= $lengths[$j]; ++$j) {
            $difference /= $lengths[$j];
        }
        $difference = round($difference);
        if ($difference != 1) {
            $periods[$j] .= 's';
            if ($periodsFr[$j] != 'mois') {
                $periodsFr[$j] .= 's';
            }
        }

        if ((int) Zend_Registry::get('configGlobal')->get('internationalization', 0) === 1) {
            /** @var SettingModel $settingModel */
            $settingModel = MidasLoader::loadModel('Setting');

            if ($settingModel->getValueByNameWithDefault('language', 'en') === 'fr') {
                if ($onlyTime) {
                    return $difference.' '.$periodsFr[$j];
                }

                return 'Il y a '.$difference.' '.$periodsFr[$j];
            }
        }

        if ($onlyTime) {
            return $difference.' '.$periods[$j];
        }

        return $difference.' '.$periods[$j].' ago';
    }

    /**
     * Format the date (ex: 5 days ago).
     *
     * @param int|string $timestamp
     * @return string
     */
    public static function duration($timestamp)
    {
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
            if ($timestamp == false) {
                return '';
            }
        }
        $difference = $timestamp;
        $periods = array('second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'decade');
        $periodsFr = array('seconde', 'minute', 'heure', 'jour', 'semaine', 'mois', 'annee', 'decades');
        $lengths = array('60', '60', '24', '7', '4.35', '12', '10');
        for ($j = 0; $difference >= $lengths[$j]; ++$j) {
            $difference /= $lengths[$j];
        }
        $difference = round($difference);
        if ($difference != 1) {
            $periods[$j] .= 's';
            if ($periodsFr[$j] != 'mois') {
                $periodsFr[$j] .= 's';
            }
        }

        if ($periods[$j] == 'second' || $periods[$j] == 'seconds') {
            $difference = $timestamp;
        }

        if ((int) Zend_Registry::get('configGlobal')->get('internationalization', 0) === 1) {
            /** @var SettingModel $settingModel */
            $settingModel = MidasLoader::loadModel('Setting');

            if ($settingModel->getValueByNameWithDefault('language', 'en') === 'fr') {
                return $difference.' '.$periodsFr[$j];
            }
        }

        return $difference.' '.$periods[$j];
    }
}
