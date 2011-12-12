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

/** Json Component */
class JsonComponent extends AppComponent
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

  /**
   * Decodes the given $encodedValue string which is
   * encoded in the JSON format
   *
   * @param string $encodedValue Encoded in JSON format
   * @param boolean
   * @return mixed
   */
  static public function decode($encodedValue, $objectDecodeType = true)
    {
    $char = substr($encodedValue, 0, 1);
    $isValue = false;
    if($char != "{")
      {
      $isValue = true;
      $encodedValue = '{"toto":'.$encodedValue.'}';
      }

    $tab = json_decode($encodedValue, $objectDecodeType);
    if($isValue)
      {
      $tab = $tab["toto"];
      }
    if(is_array($tab))
      {
      self::getInstance()->utf8_decode_array($tab);
      }
    else
      {
      $tab = utf8_decode($tab);
      }
    return $tab;
    }


  /**
   * Encode the mixed $valueToEncode into the JSON format
   *
   * @param mixed $valueToEncode
   * @return string JSON encoded object
   */
  static public function encode($valueToEncode)
    {
    if(!is_array($valueToEncode))
      {
      $valueToEncode = utf8_encode($valueToEncode);
      }
    else
      {
      self::getInstance()->utf8_encode_array($valueToEncode);
      }
    // return encoded string
    return json_encode($valueToEncode);
    }

  /** Encore Array */
  function utf8_encode_array(&$tab)
    {
    array_walk($tab, array($this, '_utf8_encode_array'));
    }

  /** Decode Array */
  function utf8_decode_array(&$tab)
    {
    array_walk($tab, array($this, '_utf8_decode_array'));
    }

  /** encode Array */
  private function _utf8_encode_array(&$array, $key)
    {
    if(is_object($array) && method_exists($array, 'toArray'))
      {
      $array = $array->toArray();
      }
    if(is_array($array))
      {
      array_walk($array, array($this, '_utf8_encode_array'));
      }
    else
      {
      $array = utf8_encode($array);
      }
    }

  /** decode array*/
  private function _utf8_decode_array(&$array, $key)
    {
    if(is_array($array))
      {
      array_walk($array, array($this, '_utf8_decode_array'));
      }
    else
      {
      $array = utf8_decode($array);
      }
    }
}
