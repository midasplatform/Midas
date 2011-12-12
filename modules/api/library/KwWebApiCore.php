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
/*=========================================================================
Program:   MIDAS Server
Language:  PHP/HTML/Java/Javascript/SQL
Date:      $Date$
Version:   $Revision$

Copyright (c) Kitware Inc. 28 Corporate Drive. All rights reserved.
Clifton Park, NY, 12065, USA.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
?>
<?php

require_once BASE_PATH . '/modules/api/library/Pear/XML/Serializer.php';
require_once BASE_PATH . '/modules/api/library/KwUploadAPI.php';

/** Midas API core functionnalities */
abstract class KwWebApiCore
{
  var $apiMethodPrefix = '';
  var $apicallbacks = array();
  var $capabilities;
  var $currentApiMessage = null;
  static $defaultCallbackName = '';

  var $testing_enable = false;

  abstract protected function apiError($error, $message);
  abstract protected function apiMessage( $request_data, $defaultFormat = true);
  abstract protected function setCapabilities( );

  function __construct($apiSetup, $apicallbacks = false, $request_data = false)
    {
    //$this->checkApiSetup($apiSetup);

    $this->apiMethodPrefix = KwWebApiCore::checkApiMethodPrefix( $apiSetup['apiMethodPrefix'] );
    $this->setCapabilities();

    $this->testing_enable = $apiSetup['testing'];

    if ($apicallbacks) {
          $this->apicallbacks = $apicallbacks;
      }
    $this->setSystemCallbacks();
    $this->setDefaultCallback( $this->apiMethodPrefix . 'system.listMethods');
    $this->handleRequest( $request_data );
    }

  /** check if the $apiSetup provided is valid */
  function checkApiSetup($apiSetup)
    {
    // Not Implemented
    }

  /** if method prefix is not empty, append a dot */
  static function checkApiMethodPrefix( $prefix )
    {
    // If 'api.methodprefix' is not an empty string, add a dot
    if ( !empty($prefix) ) { $prefix .= '.'; }
    return $prefix;
    }

  static function setDefaultCallback($methodName)
    {
    self::$defaultCallbackName = $methodName;
    }

  /** Define the list of system methods */
  private function setSystemCallbacks()
    {
    $this->apicallbacks[$this->apiMethodPrefix.'system.getCapabilities'] = 'this:getCapabilities';
    $this->apicallbacks[$this->apiMethodPrefix.'system.listMethods']     = 'this:listMethods';
    $this->apicallbacks[$this->apiMethodPrefix.'system.echo']            = 'this:apiEcho';
    //$this->apicallbacks[$this->apiMethodPrefix.'system.multicall']     = 'this:multiCall';
    }

  /** Get the list of available methods */
  function listMethods($args)
    {
    // Returns a list of methods - uses array_reverse to ensure user defined
    // methods are listed before server defined methods
    return array_reverse( array_keys( $this->apicallbacks ) );
    }

  function getCapabilities($args)
    {
    return $this->capabilities;
    }

  /** Return the method arguments - Useful for test purpose */
  function apiEcho($args)
    {
    return $args;
    }

  /** Process the request data */
  protected function handleRequest( $request_data = false )
    {
    try
      {
      $this->currentApiMessage = $this->apiMessage( $request_data );
      $this->currentApiMessage->parse( $request_data );
      $result_data = $this->call($this->currentApiMessage->methodName, $this->currentApiMessage->params);
      $this->apiMessage( $request_data , false)->output( $result_data );
      }
    catch (Exception $e)
      {
      $this->apiError($e->getCode(), $e->getMessage());
      }
    }

  /** Method wrapper - Check if the provided method name is valid and call it */
  private function call($methodname, $args)
    {
    if ( !$this->hasMethod( $methodname ) )
      {
      $this->apiError(-101, 'Server error. Requested method '.$methodname.' does not exist.');
      }
    $method = $this->apicallbacks[$methodname];

    // Are we dealing with a function or a method?
    if (is_string($method) && substr($method, 0, 5) == 'this:')
      {
      // It's a class method - check it exists
      $method = substr($method, 5);
      if (!method_exists($this, $method)) {
          $this->apiError(-102, 'Server error. Requested class method "'.$method.'" does not exist.');
      }
      // Call the method
      $result = $this->$method( $args );
      }
    else
      {
      if ( !is_callable($method) )
        {
        $this->apiError(-103, 'Server error. Requested function "' . var_export($method, true) . '" does not exist.');
        }

      // Call the function/method
      if ( is_array($method) )
        {
        $result = call_user_func_array(array(&$method[0], $method[1]), array( $args ));
        }
      else
        {
        $result = call_user_func_array($method, array( $args ));
        }
      }
    return $result;
    }

  /** Instanciate an error object using the provided $format (rest, xmlrpc, soap, ... ), $message and $error code*/
  static protected function apiErrorFactory($format, $error, $message)
    {
    switch ($format)
      {
      case KwWebApiRestCore::formatName:
        return new KwWebApiRestError($error, $message);
        break;
      case KwWebApiJsonCore::formatName:
        return new KwWebApiJsonError($error, $message);
        break;
      case KwWebApiPhpSerialCore::formatName:
        return new KwWebApiPhpSerialError($error, $message);
        break;
      case KwWebApiSoapCore::formatName:
        return new KwWebApiSoapError($error, $message);
        break;
      case KwWebApiXmlRpcCore::formatName:
        return new KwWebApiXmlRpcError($error, $message);
        break;
      default:
        die("Unknown response format:".$format);
        break;
      }
    }

  /** Instanciate a Message object using the provided $format and data */
  static protected function apiMessageFactory($format, $request_data = false)
    {
    switch ($format)
      {
      case KwWebApiRestCore::formatName:
        return new KwWebApiRestMessage( $request_data );
        break;
      case KwWebApiJsonCore::formatName:
        return new KwWebApiJsonMessage( $request_data );
        break;
      case KwWebApiPhpSerialCore::formatName:
        return new KwWebApiPhpSerialMessage( $request_data );
        break;
      case KwWebApiSoapCore::formatName:
        return new KwWebApiSoapMessage( $request_data );
        break;
      case KwWebApiXmlRpcCore::formatName:
        return new KwWebApiXmlRpcMessage( $request_data );
        break;
      default:
        die("Unknown request format:".$format);
        break;
      }
    }

  /** check if a $method has been referenced */
  function hasMethod($method)
    {
    return in_array($method, array_keys($this->apicallbacks));
    }
}

class KwWebApiRestCore extends KwWebApiCore
{
  const formatName = 'rest';

  function __construct($apiMethodPrefix, $apicallbacks = false, $request_data = false)
    {
    parent::__construct($apiMethodPrefix, $apicallbacks, $request_data);
    }

  protected function setCapabilities()
    {
    }

  static function output( $content )
    {
    $content = '<?xml version="1.0" encoding="UTF-8" ?>'."\n".$content;
    $length = strlen( $content );
    header('Connection: close');
    header('Content-Length: '.$length);
    header('Content-Type: text/xml');
    header('Date: '.date('r'));
    echo $content;
    exit;
    }

  protected function apiError($error, $message)
    {
    KwWebApiCore::apiErrorFactory($this->currentApiMessage->responseFormat, $error, $message)->output();
    }

  protected function apiMessage($request_data, $defaultFormat = true)
    {
    return KwWebApiCore::apiMessageFactory($defaultFormat ? self::formatName : $this->currentApiMessage->responseFormat, $request_data);
    }

}

class KwWebApiXmlRpcCore extends KwWebApiCore
{
  const formatName = 'xmlrpc';

  function __construct($apiMethodPrefix, $apicallbacks = false, $request_data = false)
    {
    parent::__construct($apiMethodPrefix, $apicallbacks, $request_data);
    }

  protected function setCapabilities()
    {
    // Initialises capabilities array
    $this->capabilities = array(
        'xmlrpc' => array(
            'specUrl' => 'http://www.xmlrpc.com/spec',
            'specVersion' => 1
        ),
//        'faults_interop' => array(
//            'specUrl' => 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php',
//            'specVersion' => 20010516
//        ),
//        'system.multicall' => array(
//            'specUrl' => 'http://www.xmlrpc.com/discuss/msgReader$1208',
//            'specVersion' => 1
//        ),
      );
    }

  static function output( $content )
    {
    $content = '<?xml version="1.0" encoding="UTF-8" ?>'."\n".$content;
    $length = strlen( $content );
    header('Connection: close');
    header('Content-Length: '.$length);
    header('Content-Type: text/xml');
    header('Date: '.date('r'));
    echo $content;
    exit;

    }
  protected function apiError($error, $message)
    {
    KwWebApiCore::apiErrorFactory($this->currentApiMessage->responseFormat, $error, $message)->output();
    }
  protected function apiMessage($request_data, $defaultFormat = true)
    {
    return KwWebApiCore::apiMessageFactory($defaultFormat ? self::formatName : $this->currentApiMessage->responseFormat, $request_data);
    }
}

class KwWebApiSoapCore extends KwWebApiCore
{
  const formatName = 'soap';

  function __construct($apiMethodPrefix, $apicallbacks = false, $request_data = false)
    {
    parent::__construct($apiMethodPrefix, $apicallbacks, $request_data);
    }

  protected function setCapabilities()
    {
    }

  static function output( $content )
    {
    $content = '<?xml version="1.0" encoding="UTF-8" ?>'."\n".$content;
    $length = strlen( $content );
    header('Connection: close');
    header('Content-Length: '.$length);
    header('Content-Type: text/xml');
    header('Date: '.date('r'));
    echo $content;
    exit;
    }
  protected function apiError($error, $message)
    {
    KwWebApiCore::apiErrorFactory($this->currentApiMessage->responseFormat, $error, $message)->output();
    }
  protected function apiMessage($request_data, $defaultFormat = true)
    {
    return KwWebApiCore::apiMessageFactory($defaultFormat ? self::formatName : $this->currentApiMessage->responseFormat, $request_data);
    }
}

class KwWebApiJsonCore extends KwWebApiCore
{
  const formatName = 'json';

  function __construct($apiMethodPrefix, $apicallbacks = false, $request_data = false)
    {
    parent::__construct($apiMethodPrefix, $apicallbacks, $request_data);
    }

  protected function setCapabilities()
    {
    }

  static function output($content)
    {
    echo $content;
    }
  protected function apiError($error, $message)
    {
    KwWebApiCore::apiErrorFactory($this->currentApiMessage->responseFormat, $error, $message)->output();
    }
  protected function apiMessage($request_data, $defaultFormat = true)
    {
    return KwWebApiCore::apiMessageFactory($defaultFormat ? self::formatName : $this->currentApiMessage->responseFormat, $request_data);
    }
}

class KwWebApiPhpSerialCore extends KwWebApiCore
{
  const formatName = 'php_serial';

  function __construct($apiMethodPrefix, $apicallbacks = false, $request_data = false)
    {
    parent::__construct($apiMethodPrefix, $apicallbacks, $request_data);
    }

  protected function setCapabilities()
    {
    }

  static function output( $content )
    {
    // NOT IMPLEMENTED
    echo $content;
    exit;
    }
  protected function apiError($error, $message)
    {
    KwWebApiCore::apiErrorFactory($this->currentApiMessage->responseFormat, $error, $message)->output();
    }
  protected function apiMessage($request_data, $defaultFormat = true)
    {
    return KwWebApiCore::apiMessageFactory($defaultFormat ? self::formatName : $this->currentApiMessage->responseFormat, $request_data);
    }
}


abstract class KwWebApiMessage
{
  var $methodName;
  var $params = array();
  var $request_data;
  var $responseFormat;

  abstract function parse();
  abstract function output($result_data);

  function __construct ( $request_data )
    {
    $this->request_data = $request_data;
    }
}

class KwWebApiRestMessage extends KwWebApiMessage
{
  function __construct( $request_data )
    {
    $this->responseFormat = KwWebApiRestCore::formatName;
    parent::__construct( $request_data );
    }

  function parse()
    {

    if (!$this->request_data)
      {
      // NOT IMPLEMENTED
      }
    // Get request parameters and set default
    $this->methodName =     array_key_exists('method', $this->request_data) ? $this->request_data['method'] : KwWebApiCore::$defaultCallbackName;
    $this->responseFormat = array_key_exists('format', $this->request_data) ? $this->request_data['format'] : KwWebApiRestCore::formatName;


    if ( !empty($this->methodName) )
      {
      // Get request parameters
      $this->params = $this->request_data;
      }
    else
      {
      throw new Exception('Server error. Method parameter is not defined.', -100);
      }
    }

  function output($result_data)
    {

    // using the XML_SERIALIZER Pear Package
    $options = array
      (
      XML_SERIALIZER_OPTION_INDENT           => '  ',
      XML_SERIALIZER_OPTION_XML_DECL_ENABLED => false,
      XML_SERIALIZER_OPTION_ROOT_NAME        => 'rsp',
      XML_SERIALIZER_OPTION_XML_ENCODING     => 'UTF-8',
      XML_SERIALIZER_OPTION_DEFAULT_TAG      => 'data',
      XML_SERIALIZER_OPTION_ROOT_ATTRIBS     => array("stat" => "ok"),
      XML_SERIALIZER_OPTION_RETURN_RESULT    => true
      );
    $serializer = new XML_Serializer( $options );
    
    $serialized_result = $serializer->serialize( $result_data );
    KwWebApiRestCore::output( $serialized_result );
    }
}

class KwWebApiXmlRpcMessage extends KwWebApiMessage
{
  function __construct( $request_data )
    {
    $this->responseFormat = KwWebApiXmlRpcCore::formatName;
    parent::__construct( $request_data );
    }

  function parse()
    {
    if (!$this->request_data)
      {
      // NOT IMPLEMENTED
      }
    die('NOT IMPLEMENTED');
    }
  function output($result_data)
    {
    // using the XML_SERIALIZER Pear Package
    $options = array
      (
      XML_SERIALIZER_OPTION_INDENT           => '  ',
      XML_SERIALIZER_OPTION_XML_DECL_ENABLED => false,
      XML_SERIALIZER_OPTION_XML_ENCODING     => 'UTF-8',
      XML_SERIALIZER_OPTION_DEFAULT_TAG      => 'data',
      XML_SERIALIZER_OPTION_ROOT_NAME        => 'response',
      XML_SERIALIZER_OPTION_RETURN_RESULT    => true
      );
    $serializer = new XML_Serializer( $options );
    $serialized_result = htmlentities( $serializer->serialize( $result_data ), ENT_QUOTES);

    $serialized_result = <<<EOD
<methodResponse>
  <params>
    <param>
      <value>
        <string>
          {$serialized_result}
        </string>
      </value>
    </param>
  </params>
</methodResponse>

EOD;
    KwWebApiXmlRpcCore::output( $serialized_result );
    }
}

class KwWebApiSoapMessage extends KwWebApiMessage
{
  function __construct( $request_data )
    {
    $this->responseFormat = KwWebApiSoapCore::formatName;
    parent::__construct( $request_data );
    }

  function parse()
    {
    if (!$this->request_data)
      {
      // NOT IMPLEMENTED
      }
    die('NOT IMPLEMENTED');
    }
  function output($result_data)
    {
    // using the XML_SERIALIZER Pear Package
    $options = array
      (
      XML_SERIALIZER_OPTION_INDENT           => '  ',
      XML_SERIALIZER_OPTION_XML_DECL_ENABLED => false,
      XML_SERIALIZER_OPTION_XML_ENCODING     => 'UTF-8',
      XML_SERIALIZER_OPTION_DEFAULT_TAG      => 'data',
      XML_SERIALIZER_OPTION_ROOT_NAME        => 'response',
      XML_SERIALIZER_OPTION_RETURN_RESULT    => true
      );
    $serializer = new XML_Serializer( $options );
    $serialized_result = htmlentities( $serializer->serialize( $result_data ), ENT_QUOTES);

    $serialized_result = <<<EOD
<s:Envelope
  xmlns:s="http://www.w3.org/2003/05/soap-envelope"
  xmlns:xsi="http://www.w3.org/1999/XMLSchema-instance"
  xmlns:xsd="http://www.w3.org/1999/XMLSchema"
>
  <s:Body>
    <x:MidasResponse xmlns:x="urn:midas">
      {$serialized_result}
    </x:MidasResponse>
  </s:Body>
</s:Envelope>

EOD;
    KwWebApiSoapCore::output( $serialized_result );
    }
}

class KwWebApiJsonMessage extends KwWebApiMessage
{
  function __construct( $request_data )
    {
    $this->responseFormat = KwWebApiJsonCore::formatName;
    parent::__construct( $request_data );
    }

  function parse()
    {
    if (!$this->request_data)
      {
      // NOT IMPLEMENTED
      }
    die('NOT IMPLEMENTED');
    }
  function output($result_data)
    {
    $outputJson = array();
    $outputJson['stat'] = 'ok';
    $outputJson['code'] = '0';
    $outputJson['message'] = '';
    $outputJson['data'] = $result_data;

    KwWebApiJsonCore::output( json_encode($outputJson) );
    }
}

class KwWebApiPhpSerialMessage extends KwWebApiMessage
{
  function __construct( $request_data )
    {
    $this->responseFormat = KwWebApiPhpSerialCore::formatName;
    parent::__construct( $request_data );
    }

  function parse()
    {
    if (!$this->request_data)
      {
      // NOT IMPLEMENTED
      }
    die('NOT IMPLEMENTED');
    }
  function output($result_data)
    {
    $serialized_result = 'NOT IMPLEMENTED';
    // NOT IMPLEMENTED
    KwWebApiPhpSerialCore::output( $serialized_result );
    }
}

abstract class KwWebApiError
{
  abstract function output();

  var $code;
  var $message;

  function __construct($code, $message) {
    $this->code = $code;
    $this->message = $message;
  }


}

class KwWebApiRestError extends KwWebApiError
{
  function __construct($code, $message)
    {
    parent::__construct($code, $message);
    }

  function output()
    {
    $result = <<<EOD
<rsp stat="fail">
  <err code="{$this->code}" msg="{$this->message}" />
</rsp>

EOD;
    KwWebApiRestCore::output( $result );
    }
}

class KwWebApiSoapError extends KwWebApiError
{
  function __construct($code, $message)
    {
    parent::__construct($code, $message);
    }

  function output()
    {
    $result = <<<EOD
<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">
  <s:Body>
    <s:Fault>
      <faultcode>midas.error.{$this->code}</faultcode>
      <faultstring>{$this->message}</faultstring>
      <faultactor>
        http://XXXX/midas/api/soap/
      </faultactor>
      <details>
        Please see
        http://XXXX/midas/api/
        for more details
      </details>
    </s:Fault>
  </s:Body>
</s:Envelope>

EOD;
    KwWebApiSoapCore::output( $result );
    }
}

class KwWebApiXmlRpcError extends KwWebApiError
{
  function __construct($code, $message)
    {
    parent::__construct($code, $message);
    }

  function output()
    {
    $result = <<<EOD
<methodResponse>
  <fault>
    <value>
      <struct>
        <member>
          <name>faultCode</name>
          <value><int>{$this->code}</int></value>
        </member>
        <member>
          <name>faultString</name>
          <value><string>{$this->message}</string></value>
        </member>
      </struct>
    </value>
  </fault>
</methodResponse>

EOD;
    KwWebApiXmlRpcCore::output( $result );
    }
}

class KwWebApiPhpSerialError extends KwWebApiError
{
  function __construct($code, $message)
    {
    parent::__construct($code, $message);
    }

  function output()
    {
    $result = '';
    // NOT IMPLEMENTED
    KwWebApiPhpSerialCore::output( $result );
    }
}

class KwWebApiJsonError extends KwWebApiError
{
  function __construct($code, $message)
    {
    parent::__construct($code, $message);
    }

  function output()
    {
    $result = array();
    $result['stat'] = 'fail';
    $result['message'] = $this->message;
    $result['code'] = $this->code;

    KwWebApiJsonCore::output( json_encode($result) );
    }
}


?>