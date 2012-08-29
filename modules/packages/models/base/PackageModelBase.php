<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
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

  public abstract function getAll();
  public abstract function getByItemId($itemId);
  public abstract function getLatestOfEachPackageType($application, $os, $arch, $submissiontype = null);
}
