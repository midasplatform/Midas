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
 * Legacy component loader.
 *
 * @deprecated replaced by static methods of MidasLoader
 */
class MIDAS_ComponentLoader
{
    /**
     * Load multiple components into the Zend registry.
     *
     * @param array|string $components names of the components to load
     * @param string $module name of the module from which to load the components, defaults to core
     * @deprecated replaced by AppComponent MidasLoader::loadComponent(string $component, string $module)
     */
    public function loadComponents($components, $module = '')
    {
        if (is_string($components)) {
            MidasLoader::loadComponent($components, $module);
        } elseif (is_array($components)) {
            foreach ($components as $component) {
                MidasLoader::loadComponent($component, $module);
            }
        }
    }

    /**
     * Load a component into the Zend registry.
     *
     * @param string $component name of the component to load
     * @param string $module name of the module from which to load the component, defaults to core
     * @return mixed|AppComponent component that was loaded
     * @throws Zend_Exception
     * @deprecated replaced by AppComponent MidasLoader::loadComponent(string $component, string $module)
     */
    public function loadComponent($component, $module = '')
    {
        return MidasLoader::loadComponent($component, $module);
    }
}
