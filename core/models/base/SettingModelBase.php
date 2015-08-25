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

require_once BASE_PATH.'/core/models/dao/SettingDao.php';

/** Configuration setting base model class. */
abstract class SettingModelBase extends AppModel
{
    /** Constructor. */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'setting';
        $this->_key = 'setting_id';

        $this->_mainData = array(
            'setting_id' => array('type' => MIDAS_DATA),
            'name' => array('type' => MIDAS_DATA),
            'module' => array('type' => MIDAS_DATA),
            'value' => array('type' => MIDAS_DATA),
        );
        $this->initialize(); // required
    }

    /**
     * Return a configuration setting given its name and module name.
     *
     * @param string $name configuration setting name
     * @param string $module module name
     * @return false|SettingDao configuration setting DAO or false on failure
     * @throws Zend_Exception
     */
    abstract public function getDaoByName($name, $module = 'core');

    /**
     * Return the value of a configuration setting given its name and module
     * name.
     *
     * @param string $name configuration setting name
     * @param string $module module name
     * @return bool|int|float|string|void configuration setting value or void on failure
     * @throws Zend_Exception
     */
    public function getValueByName($name, $module = 'core')
    {
        $settingDao = $this->getDaoByName($name, $module);
        if ($settingDao === false) {
            return;
        }

        return $settingDao->getValue();
    }

    /** Set Configuration value.  */

    /**
     * Set the value of a configuration setting given its name and module name.
     * Set value to null to delete the configuration setting.
     *
     * @param string $name configuration setting name
     * @param bool|int|float|string|void $value configuration setting value or
     *     null to delete the configuration setting
     * @param string $module module name
     * @return false|SettingDao configuration setting DAO or false if the
     *     configuration setting was deleted
     * @throws Zend_Exception
     */
    public function setConfig($name, $value, $module = 'core')
    {
        if (!is_string($name)) {
            throw new Zend_Exception('Configuration setting name is not a string.');
        }
        if (!is_bool($value) && !is_numeric($value) && !is_string($value)) {
            throw new Zend_Exception('Configuration setting value is not a boolean, number, or string.');
        }
        if (!is_string($module)) {
            throw new Zend_Exception('Configuration setting module name is not a string.');
        }
        $settingDao = $this->getDaoByName($name, $module);
        if ($settingDao === false) {
            $settingDao = $this->initDao('Setting', array(
                'name' => $name,
                'value' => $value,
                'module' => $module,
            ));
            $this->save($settingDao);
        } elseif ($value === null) {
            $this->delete($settingDao);
            $settingDao = false;
        } elseif ($settingDao->getValue() !== $value) {
            $settingDao->setValue($value);
            $this->save($settingDao);
        }

        return $settingDao;
    }
}
