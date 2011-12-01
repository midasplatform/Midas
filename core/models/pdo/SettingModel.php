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

require_once BASE_PATH.'/core/models/base/SettingModelBase.php';

/**
 *  SettingModel
 *  Pdo Model
 */
class SettingModel extends SettingModelBase
{
  /** get by name*/
  function  getDaoByName($name, $module = 'core')
    {
    if(!is_string($name) || !is_string($module))
      {
      throw new Zend_Exception('Error Parameters');
      }
    $row = $this->database->fetchRow($this->database->select()->where('name = ?', $name)->where('module = ?', $module));
    $dao = $this->initDao(ucfirst($this->_name), $row);
    return $dao;
    }
}// end class