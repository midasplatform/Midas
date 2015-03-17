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

/**
 * Legacy model loader.
 *
 * @deprecated replaced by static methods of MidasLoader
 * @package Core\Deprecated
 */
class MIDAS_ModelLoader
{
    /**
     * Load multiple models into the Zend registry.
     *
     * @param array|string $models names of the models to load
     * @param string $module name of the module from which to load the models, defaults to core
     * @deprecated replaced by void MidasLoader::loadModels(array|string $model, string $module)
     */
    public function loadModels($models, $module = '')
    {
        MidasLoader::loadModels($models, $module);
    }

    /**
     * Load a model into the Zend registry.
     *
     * @param string $model name of the model to load
     * @param string $module name of the module from which to load the model, defaults to core
     * @return mixed|MIDASModel model that was loaded
     * @throws Zend_Exception
     * @deprecated replaced by MIDASModel MidasLoader::loadModel(string $model, string $module)
     */
    public function loadModel($model, $module = '')
    {
        return MidasLoader::loadModel($model, $module);
    }
}
