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

/**
 * Package Model Base
 */
abstract class Packages_PackageModelBase extends Packages_AppModel
  {
  /** constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'packages_package';
    $this->_key = 'package_id';
    $this->_mainData = array(
        'package_id' => array('type' => MIDAS_DATA),
        'item_id' => array('type' => MIDAS_DATA),
        'application_id' => array('type' => MIDAS_DATA),
        'os' => array('type' => MIDAS_DATA),
        'arch' => array('type' => MIDAS_DATA),
        'revision' => array('type' => MIDAS_DATA),
        'submissiontype' => array('type' => MIDAS_DATA),
        'packagetype' => array('type' => MIDAS_DATA),
        'productname' => array('type' => MIDAS_DATA),
        'codebase' => array('type' => MIDAS_DATA),
        'checkoutdate' => array('type' => MIDAS_DATA),
        'release' => array('type' => MIDAS_DATA),
        'item' => array('type' => MIDAS_MANY_TO_ONE,
                         'model' => 'Item',
                         'parent_column' => 'item_id',
                         'child_column' => 'item_id'),
        'application' => array('type' => MIDAS_MANY_TO_ONE,
                               'model' => 'Application',
                               'module' => 'packages',
                               'parent_column' => 'application_id',
                               'child_column' => 'application_id')
      );
    $this->initialize(); // required
    } // end __construct()

  abstract public function getAll();
  abstract public function getByItemId($itemId);
  abstract public function getLatestOfEachPackageType($application, $os, $arch, $submissiontype = null);
  }
