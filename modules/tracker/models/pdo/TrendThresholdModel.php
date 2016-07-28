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

require_once BASE_PATH.'/modules/tracker/models/base/TrendThresholdModelBase.php';

/** Trend Threshold model for the tracker module. */
class Tracker_TrendThresholdModel extends Tracker_TrendThresholdModelBase
{
    /**
      * Create or update a Tracker TrendThreshold tied to the Producer and metric_name.
      *
      * @param Tracker_ProducerDao $producerDao
      * @param string $metricName metric name of the trend threshold
      * @param false|string $abbreviation name abbreviation for the threshold
      * @param false|float $warning warning value for this threshold
      * @param false|float $fail fail value for this threshold
      * @param false|float $min min value for display of this threshold
      * @param false|float $max max value for display of this threshold
      * @param false|bool $lowerIsBetter whether lower values are better for this threshold
      * @return Tracker_TrendThresholdDao updated or created DAO
      */
     public function upsert(
        $producerDao,
        $metricName,
        $abbreviation = false,
        $warning = false,
        $fail = false,
        $min = false,
        $max = false,
        $lowerIsBetter = false)
     {
         if (is_null($producerDao) || $producerDao === false) {
             return false;
         }
         $sql = $this->database->select()->setIntegrityCheck(false)
            ->where('producer_id = ?', $producerDao->getProducerId())
            ->where('metric_name = ?', $metricName);
        /** @var Tracker_TrendThresholdDao $trendThresholdDao */
        $trendThresholdDao = $this->initDao('TrendThreshold', $this->database->fetchRow($sql), $this->moduleName);
         if ($trendThresholdDao === false) {
             $trendThresholdDao = MidasLoader::newDao('TrendThresholdDao', $this->moduleName);
         }
         if ($abbreviation !== false) {
             $trendThresholdDao->setAbbreviation($abbreviation);
         }
         if ($warning !== false) {
             $trendThresholdDao->setWarning($warning);
         }
         if ($fail !== false) {
             $trendThresholdDao->setFail($fail);
         }
         if ($min !== false) {
             $trendThresholdDao->setMin($min);
         }
         if ($max !== false) {
             $trendThresholdDao->setMax($max);
         }
         if ($lowerIsBetter !== false) {
             $trendThresholdDao->setLowerIsBetter($lowerIsBetter);
         }
         $this->save($trendThresholdDao);

         return $trendThresholdDao;
     }
}
