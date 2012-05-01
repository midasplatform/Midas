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
 * Packages Extension Model Base
 */
abstract class Packages_ExtensionModelBase extends Packages_AppModel
{
  /** constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'packages_extension';
    $this->_key = 'extension_id';

    $this->_mainData = array(
        'extension_id' => array('type' => MIDAS_DATA),
        'item_id' => array('type' => MIDAS_DATA),
        'application_id' => array('type' => MIDAS_DATA),
        'os' => array('type' => MIDAS_DATA),
        'arch' => array('type' => MIDAS_DATA),
        'repository_type' => array('type' => MIDAS_DATA),
        'repository_url' => array('type' => MIDAS_DATA),
        'revision' => array('type' => MIDAS_DATA),
        'submissiontype' => array('type' => MIDAS_DATA),
        'packagetype' => array('type' => MIDAS_DATA),
        'slicer_revision' => array('type' => MIDAS_DATA),
        'icon_url' => array('type' => MIDAS_DATA),
        'release' => array('type' => MIDAS_DATA),
        'productname' => array('type' => MIDAS_DATA),
        'codebase' => array('type' => MIDAS_DATA),
        'development_status' => array('type' => MIDAS_DATA),
        'category' => array('type' => MIDAS_DATA),
        'description' => array('type' => MIDAS_DATA),
        'enabled' => array('type' => MIDAS_DATA),
        'homepage' => array('type' => MIDAS_DATA),
        'screenshots' => array('type' => MIDAS_DATA),
        'contributors' => array('type' => MIDAS_DATA),
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
  public abstract function getAllCategories();
  public abstract function getAllReleases();

  /**
   * If an extension already exists that matches the given
   * extension metadata, it is returned.  Otherwise, returns null.
   */
  public function matchExistingExtension($params)
    {
    // Only filter by a subset of the parameters
    $results = $this->get(array(
      'os' => $params['os'],
      'arch' => $params['arch'],
      'application_id' => $params['application_id'],
      'repository_type' => $params['repository_type'],
      'application_revision' => $params['application_revision'],
      'packagetype' => $params['packagetype'],
      'codebase' => $params['codebase'],
      'productname' => $params['productname']));
    if($results['total'] == 0)
      {
      return null;
      }
    else
      {
      return $results['extensions'][0];
      }
    }

}
