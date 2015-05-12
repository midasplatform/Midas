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

/** Internationalization tools */
class InternationalizationComponent extends AppComponent
{
    /** @var null|InternationalizationComponent */
    private static $_instance = null;

    /**
     * Instance.
     *
     * @return InternationalizationComponent
     */
    public static function getInstance()
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Translate.
     *
     * @param string $text
     * @return string
     */
    public static function translate($text)
    {
        if (Zend_Registry::get('configGlobal')->application->lang != 'en') {
            $translate = Zend_Registry::get('translator');
            $new_text = $translate->_($text);
            if ($new_text == $text) {
                $translators = Zend_Registry::get('translatorsModules');
                foreach ($translators as $t) {
                    $new_text = $t->_($text);
                    if ($new_text != $text) {
                        break;
                    }
                }
            }

            return $new_text;
        }

        return $text;
    }

    /**
     * Is Debug mode ON.
     *
     * @return bool
     */
    public static function isDebug()
    {
        return Zend_Registry::get('configGlobal')->environment !== 'production';
    }
}
