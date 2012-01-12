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

require_once BASE_PATH.'/modules/api/library/KwWebApiCore.php';

/** Main controller for the web api module */
class Api_IndexController extends Api_AppController
{
  public $_moduleComponents = array('Api');

  var $kwWebApiCore = null;
  var $apicallbacks = array();
  var $helpContent = array();

  // Config parameters
  var $apiEnable = '';
  var $apiSetup = array();

  /** Before filter */
  function preDispatch()
    {
    parent::preDispatch();
    $this->apiEnable = true;

    // define api parameters
    $modulesConfig = Zend_Registry::get('configsModules');
    $this->apiSetup['testing'] = Zend_Registry::get('configGlobal')->environment == 'testing';
    $this->apiSetup['tmpDirectory'] = $this->getTempDirectory();
    $this->apiSetup['apiMethodPrefix'] = $modulesConfig['api']->methodprefix;

    $this->action = $actionName = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    switch($this->action)
      {
      case "rest":
      case "json":
      case "php_serial":
      case "xmlrpc":
      case "soap":
        $this->_initApiCommons();
        break;
      default:
        break;
      }
    }

  /** Index function */
  function indexAction()
    {
    $this->view->header = 'Web API';
    $this->_computeApiHelp($this->apiSetup['apiMethodPrefix']);

    // Prepare the data used by the view
    $data = array(
      'api.enable'        => $this->apiEnable,
      'api.methodprefix'  => $this->apiSetup['apiMethodPrefix'],
      'api.listmethods'   => array_keys($this->apicallbacks),
      );

    $this->view->data = $data; // transfer data to the view
    $this->view->help = $this->helpContent;
    $this->view->serverURL = $this->getServerURL();
    }

  /** This is called when calling a web api method */
  private function _computeApiCallback($method_name, $apiMethodPrefix)
    {
    $tokens = explode('.', $method_name);
    if(array_shift($tokens) != $apiMethodPrefix) //pop off the method prefix token
      {
      return; //let the web API core write out its method doesn't exist message
      }

    $method = implode($tokens);
    if(method_exists($this->ModuleComponent->Api, $method))
      {
      $this->apicallbacks[$method_name] = array(&$this->ModuleComponent->Api, $method);
      }
    else //it doesn't exist here, check in the module specified by the 2nd token
      {
      $moduleName = array_shift($tokens);
      $moduleMethod = implode('', $tokens);
      $retVal = Zend_Registry::get('notifier')->callback('CALLBACK_API_METHOD_'.strtoupper($moduleName), array('methodName' => $moduleMethod));
      foreach($retVal as $module => $method)
        {
        $this->apicallbacks[$method_name] = array($method['object'], $method['method']);
        break;
        }
      }
    }

  /** This index function uses this to display the list of web api methods */
  private function _computeApiHelp($apiMethodPrefix)
    {
    $apiMethodPrefix = KwWebApiCore::checkApiMethodPrefix($apiMethodPrefix); //append the . if needed

    // Get the list of methods in each module (including this one)
    $apiMethods = Zend_Registry::get('notifier')->callback('CALLBACK_API_HELP', array());
    foreach($apiMethods as $module => $methods)
      {
      foreach($methods as $method)
        {
        $apiMethodName = $apiMethodPrefix;
        if($module != $this->moduleName) //for functions in this module, don't append module name
          {
          $apiMethodName .= $module.'.';
          }
        $apiMethodName .= $method['name'];
        $this->helpContent[$apiMethodName] = $method['help'];
        $this->apicallbacks[$apiMethodName] = array($method['callbackObject'], $method['callbackFunction']);
        }
      }
    }

  /** Initialize property allowing to generate XML */
  private function _initApiCommons()
    {
    // Disable debug information - Required to generate valid XML output
    //Configure::write('debug', 0);

    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $this->ModuleComponent->Api->controller = &$this;
    $this->ModuleComponent->Api->apiSetup = &$this->apiSetup;
    $this->ModuleComponent->Api->userSession = &$this->userSession;
    }

  /** Controller action handling REST request */
  function restAction()
    {
    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $request_data = $this->_getAllParams();

    $method_name = $this->_getParam('method');
    if(!isset($method_name))
      {
      echo 'Inconsistent request: please set a method parameter';
      exit;
      }

    $request_data = $this->_getAllParams();
    $this->_computeApiCallback($method_name, $this->apiSetup['apiMethodPrefix']);
    // Handle XML-RPC request
    $this->kwWebApiCore = new KwWebApiRestCore($this->apiSetup, $this->apicallbacks, $request_data);
    }

  /** Controller action handling JSON request */
  function jsonAction()
    {
    $this->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $request_data = $this->_getAllParams();

    $method_name = $this->_getParam('method');
    if(!isset($method_name))
      {
      echo 'Inconsistent request: please set a method parameter';
      exit;
      }

    $request_data = $this->_getAllParams();
    $this->_computeApiCallback($method_name, $this->apiSetup['apiMethodPrefix']);
    // Handle XML-RPC request
    $this->kwWebApiCore = new KwWebApiRestCore($this->apiSetup, $this->apicallbacks, array_merge($request_data, array('format' => 'json')));
    }

  /** Allows components to call redirect */
  function redirect($url)
    {
    $this->_redirect($url);
    }
  } // end class
?>
