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

/** Base item metric model for the batchmake module */
abstract class Batchmake_ItemmetricModelBase extends Batchmake_AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'batchmake_itemmetric';
        $this->_key = 'itemmetric_id';

        $this->_mainData = array(
            'itemmetric_id' => array('type' => MIDAS_DATA),
            'metric_name' => array('type' => MIDAS_DATA),
            'bms_name' => array('type' => MIDAS_DATA),
        );
        $this->initialize(); // required
    }

    /**
     * Create an item metric.
     *
     * @param string $metricName
     * @param string $bmsName
     * @return Batchmake_ItemmetricDao
     * @throws Zend_Exception  if an item metric already exists with this metric name
     */
    public function createItemmetric($metricName, $bmsName)
    {
        /** @var Batchmake_ItemmetricDao $itemmetric */
        $itemmetric = MidasLoader::newDao('ItemmetricDao', 'batchmake');

        // make sure one isn't already there by this name
        $found = $this->findBy('metric_name', $metricName);
        if (isset($found) && count($found) > 0) {
            // don't allow the creation, as we have a metric of this name already
            throw new Zend_Exception('An Itemmetric already exists with that name');
        }

        $itemmetric->setMetricName($metricName);
        $itemmetric->setBmsName($bmsName);
        $this->save($itemmetric);

        return $itemmetric;
    }

    /**
     * Get all rows stored.
     *
     * @return array
     */
    abstract public function getAll();
}
