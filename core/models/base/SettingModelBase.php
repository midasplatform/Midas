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
require_once BASE_PATH.'/core/models/dao/SettingDao.php';

/** Setting Model Base*/
abstract class SettingModelBase extends AppModel
{
  /** Constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'setting';
    $this->_key = 'setting_id';

    $this->_mainData = array(
        'setting_id' =>  array('type' => MIDAS_DATA),
        'name' =>  array('type' => MIDAS_DATA),
        'module' =>  array('type' => MIDAS_DATA),
        'value' =>  array('type' => MIDAS_DATA)
        );
    $this->initialize(); // required
    } // end __construct()

  /** Abstract functions */
  abstract function getDaoByName($name, $module = 'core');

  /** get value by name */
  public function getValueByName($name, $module = 'core')
    {
    $dao = $this->getDaoByName($name, $module);
    if($dao == false)
      {
      return null;
      }
    return $dao->getValue();
    }

  /** Set Configuration value. Set value as null to delete */
  public function setConfig($name, $value, $module = 'core')
    {
    if(!is_string($name) || !is_string($value) || !is_string($module))
      {
      throw new Zend_Exception('Error Parameters');
      }
    $dao = $this->getDaoByName($name, $module);
    if($dao != false && $dao->getValue() == $value)
      {
      return;
      }
    if($dao != false && $value === null)
      {
      $this->delete($previousDao);
      }
    elseif($dao != false)
      {
      $dao->setValue($value);
      $this->save($dao);
      }
    else
      {
      $dao = new SettingDao ();
      $dao->setName($name);
      $dao->setModule($module);
      $dao->setValue($value);
      $this->save($dao);
      }
    return $dao;
    }

} // end class AssetstoreModelBase