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
    if(!is_string($name))
      {
      throw new Zend_Exception('SettingModelBase.setConfig: Error in Parameter: name is not a string');
      }
    if(!is_string($value))
      {
      throw new Zend_Exception('SettingModelBase.setConfig: Error in Parameter: value is not a string');
      }
    if(!is_string($module))
      {
      throw new Zend_Exception('SettingModelBase.setConfig: Error in Parameter: module is not a string');
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
    else if($dao != false)
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
  }
