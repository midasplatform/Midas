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

abstract class Batchmake_ItemmetricModelBase extends Batchmake_AppModel
  {
  /**
   * constructor
   */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'batchmake_itemmetric';
    $this->_key = 'itemmetric_id';

    $this->_mainData = array(
      'itemmetric_id' => array('type' => MIDAS_DATA),
      'metric_name' => array('type' => MIDAS_DATA),
      'bms_name' => array('type' => MIDAS_DATA, )
      );
    $this->initialize(); // required
    }

  /** Create an Itemmetric
   * @return ItemmetricDao, will throw a Zend_Exception if an
   * Itemmetric already exists with this metricName
   */
  public function createItemmetric($metricName, $bmsName)
    {
    $itemmetric = MidasLoader::newDao('ItemmetricDao', 'batchmake');

    // make sure one isn't already there by this name
    $found = $this->findBy('metric_name', $metricName);
    if(isset($found) && sizeof($found) > 0)
      {
      // don't allow the creation, as we have a metric of this name already
      throw new Zend_Exception('An Itemmetric already exists with that name');
      }

    $itemmetric->setMetricName($metricName);
    $itemmetric->setBmsName($bmsName);
    $this->save($itemmetric);
    return $itemmetric;
    } // end createItemmetric()

  /**
   * getAll returns all rows
   */
  public abstract function getAll();
  } // end class
