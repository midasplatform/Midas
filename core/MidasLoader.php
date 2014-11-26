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
 * Static methods used to load model, component, and DAO objects. Handles
 * importing the required file and instantiating the object of the desired
 * type.
 *
 * @package Core
 */
class MidasLoader
{
    /**
     * Load a component into the Zend registry.
     *
     * @param string $component name of the component to load
     * @param string $module name of the module from which to load the component, defaults to core
     * @return mixed|AppComponent component that was loaded
     * @throws Zend_Exception
     */
    public static function loadComponent($component, $module = '')
    {
        $components = Zend_Registry::get('components');
        if (!isset($components[$module.$component])) {
            if ($module == '') {
                include_once BASE_PATH.'/core/controllers/components/'.$component.'Component.php';
                $name = $component.'Component';
            } else {
                if (file_exists(
                    BASE_PATH.'/modules/'.$module.'/controllers/components/'.$component.'Component.php'
                )) {
                    include_once BASE_PATH.'/modules/'.$module.'/controllers/components/'.$component.'Component.php';
                } elseif (file_exists(
                    BASE_PATH.'/privateModules/'.$module.'/controllers/components/'.$component.'Component.php'
                )) {
                    include_once BASE_PATH.'/privateModules/'.$module.'/controllers/components/'.$component.'Component.php';
                } else {
                    throw new Zend_Exception("A component named ".$component." doesn't "."exist.");
                }

                $name = ucfirst($module).'_'.$component.'Component';
            }
            if (class_exists($name)) {
                $components[$module.$component] = new $name();
                Zend_Registry::set('components', $components);
            } else {
                throw new Zend_Exception('Unable to load class '.$name);
            }
        }

        return $components[$module.$component];
    }

    /**
     * Load a model into the Zend registry.
     *
     * @param string $model name of the model to load
     * @param string $module name of the module from which to load the model, defaults to core
     * @return mixed|MIDASModel model that was loaded
     * @throws Zend_Exception
     */
    public static function loadModel($model, $module = '')
    {
        $models = Zend_Registry::get('models');

        if (!isset($models[$module.$model])) {
            if ($module == '') {
                if (file_exists(BASE_PATH.'/core/models/base/'.$model.'ModelBase.php')) {
                    include_once BASE_PATH.'/core/models/base/'.$model.'ModelBase.php';
                }

                include_once BASE_PATH.'/core/models/pdo/'.$model.'Model.php';
                $name = $model.'Model';
            } else {
                if (file_exists(BASE_PATH.'/modules/'.$module.'/models/base/'.$model.'ModelBase.php')) {
                    include_once BASE_PATH.'/modules/'.$module.'/models/base/'.$model.'ModelBase.php';
                } elseif (file_exists(
                    BASE_PATH.'/privateModules/'.$module.'/models/base/'.$model.'ModelBase.php'
                )) {
                    include_once BASE_PATH.'/privateModules/'.$module.'/models/base/'.$model.'ModelBase.php';
                }

                if (file_exists(BASE_PATH.'/modules/'.$module.'/models/pdo/'.$model.'Model.php')) {
                    include_once BASE_PATH.'/modules/'.$module.'/models/pdo/'.$model.'Model.php';
                } elseif (file_exists(
                    BASE_PATH.'/privateModules/'.$module.'/models/pdo/'.$model.'Model.php'
                )) {
                    include_once BASE_PATH.'/privateModules/'.$module.'/models/pdo/'.$model.'Model.php';
                } else {
                    throw new Zend_Exception("Unable to find model file ".$model);
                }

                $name = ucfirst($module).'_'.$model.'Model';
            }

            if (class_exists($name)) {
                $models[$module.$model] = new $name();
                Zend_Registry::set('models', $models);
            } else {
                throw new Zend_Exception('Unable to load class '.$name);
            }
        }

        return $models[$module.$model];
    }

    /**
     * Load multiple models into the Zend registry.
     *
     * @param array|string $models names of the models to load
     * @param string $module name of the module from which to load the models, defaults to core
     */
    public static function loadModels($models, $module = '')
    {
        if (is_string($models)) {
            self::loadModel($models, $module);
        } elseif (is_array($models)) {
            foreach ($models as $model) {
                self::loadModel($model, $module);
            }
        }
    }

    /**
     * Instantiate a new DAO.
     *
     * @param string $name base name with no module prefix of the DAO to load
     * @param string $module name of the module from which to instantiate the DAO, defaults to core
     * @return mixed|MIDAS_GlobalDao DAO that was instantiated
     * @throws Zend_Exception
     */
    public static function newDao($name, $module = 'core')
    {
        if ($module == 'core') {
            Zend_Loader::loadClass($name, BASE_PATH.'/core/models/dao');

            if (!class_exists($name)) {
                throw new Zend_Exception('Unable to load dao class '.$name);
            }

            return new $name();
        } else {
            if (file_exists(BASE_PATH.'/modules/'.$module.'/models/dao/'.$name.'.php')) {
                require_once BASE_PATH.'/modules/'.$module.'/models/dao/'.$name.'.php';
            } elseif (file_exists(BASE_PATH.'/privateModules/'.$module.'/models/dao/'.$name.'.php')) {
                require_once BASE_PATH.'/privateModules/'.$module.'/models/dao/'.$name.'.php';
            } else {
                throw new Zend_Exception("Unable to find dao file ".$name);
            }

            $classname = ucfirst($module).'_'.$name;

            if (!class_exists($classname)) {
                throw new Zend_Exception('Unable to load dao class '.$classname);
            }

            return new $classname();
        }
    }
}
