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

/** Internationalization tools */
class InternationalizationComponent extends AppComponent
  {
  static private $_instance = null;

  /** Constructor */
  function __construct()
    {
    }

  /** Instance */
  static public function getInstance()
    {
    if(!self::$_instance instanceof self)
      {
      self::$_instance = new self();
      }
    return self::$_instance;
    }

  /** translate*/
  static public function translate($text)
    {
    if(Zend_Registry::get('configGlobal')->application->lang == 'fr')
      {
      $translate = Zend_Registry::get('translater');
      $new_text = $translate->_($text);
      if($new_text == $text)
        {
        $translaters = Zend_Registry::get('translatersModules');
        foreach($translaters as $t)
          {
          $new_text = $t->_($text);
          if($new_text != $text)
            {
            break;
            }
          }
        }
      return $new_text;
      }
    return $text;
    } //end method t

  /**
   * @method public  isDebug()
   * Is Debug mode ON
   * @return boolean
   */
  static public function isDebug()
    {
    $config = Zend_Registry::get('config');
    if($config->mode->debug == 1)
      {
      return true;
      }
    else
      {
      return false;
      }
    }
} // end class