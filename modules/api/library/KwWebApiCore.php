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

/** API core functionality */
class KwWebApiCore
{
    /** @var array */
    public $apiCallbacks;

    /** @var string */
    public $apiMethodPrefix;

    /**
     * Constructor.
     *
     * @param array $apiMethodPrefix method prefix
     * @param array $apiCallbacks
     * @param array $requestData request data
     */
    public function __construct($apiMethodPrefix, $apiCallbacks, $requestData)
    {
        $this->apiMethodPrefix = self::checkApiMethodPrefix($apiMethodPrefix);
        $this->apiCallbacks = $apiCallbacks;
        $this->apiCallbacks[$this->apiMethodPrefix.'system.listMethods'] = 'this:listMethods';
        $this->handleRequest($requestData);
    }

    /**
     * If method prefix is not empty, append a dot.
     *
     * @param string $apiMethodPrefix method prefix
     * @return string
     */
    public static function checkApiMethodPrefix($apiMethodPrefix)
    {
        if (!empty($apiMethodPrefix)) {
            $apiMethodPrefix .= '.';
        }

        return $apiMethodPrefix;
    }

    /**
     * Return the list of available methods.
     *
     * @param array $args parameters
     * @return array
     */
    public function listMethods($args)
    {
        // Use array_reverse to ensure that user-defined methods are listed before server-defined methods.
        return array_reverse(array_keys($this->apiCallbacks));
    }

    /**
     * Process the request data.
     *
     * @param array $requestData request data
     */
    protected function handleRequest($requestData)
    {
        try {
            $methodName = isset($requestData['method']) ? $requestData['method'] : $this->apiMethodPrefix.'system.listMethods';
            $resultData = $this->call($methodName, $requestData);

            $output = array(
                'code' => 0,
                'data' => $resultData,
                'message' => '',
                'stat' => 'ok',
            );
        } catch (Exception $exception) {
            $output = array(
                'code' => $exception->getCode(),
                'data' => '',
                'message' => $exception->getMessage(),
                'stat' => 'fail',
            );
        }

        echo JsonComponent::encode($output);
    }

    /**
     * Check whether the provided method name is valid and then call it.
     *
     * @param string $methodName method name
     * @param array $args parameters
     * @return mixed
     * @throws Exception
     */
    private function call($methodName, $args)
    {
        if (!in_array($methodName, array_keys($this->apiCallbacks))) {
            throw new Exception('Server error. Requested method '.$methodName.' does not exist.', -101);
        }

        $method = $this->apiCallbacks[$methodName];

        // Are we dealing with a function or a method?
        if (is_string($method) && substr($method, 0, 5) == 'this:') {
            // It is a class method - check it exists
            $method = substr($method, 5);

            if (!method_exists($this, $method)) {
                throw new Exception('Server error. Requested class method "'.$method.'" does not exist.', -102);
            }

            // Call the method
            return $this->$method($args);
        }

        if (!is_callable($method)) {
            throw new Exception('Server error. Requested function "'.var_export($method, true).'" does not exist.', -103);
        }

        // Call the function/method
        if (is_array($method)) {
            return call_user_func_array(array(&$method[0], $method[1]), array($args));
        }

        return call_user_func_array($method, array($args));
    }
}
