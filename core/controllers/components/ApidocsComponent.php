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

/** These are the implementations to generate the web api documents */
class ApidocsComponent extends AppComponent
  {

  /**
   * This function is for getting the webapi methods information defined in
   * all the API component of the implementing class.
   */
  public function getEnabledResources()
    {
    $apiResources = array();

    foreach(glob(BASE_PATH.'/core/controllers/components/Api*.php') as $filename)
      {
      $resoucename = preg_replace('/Component\.php/', '', substr(basename($filename), 3));
      if(!in_array($resoucename, array('helper', 'docs')))
        {
        $apiResources[] = '/'. $resoucename;
        }
      }

    $modulesHaveApi = Zend_Registry::get('modulesHaveApi');
    $modulesEnabled = Zend_Registry::get('modulesEnable');
    $apiModules = array_intersect($modulesHaveApi, $modulesEnabled);
    foreach($apiModules as $module)
      {
      foreach(glob(BASE_PATH.'/modules/'.$module.'/controllers/components/Api*.php') as $filename)
        {
        $resoucename = preg_replace('/Component\.php/', '', substr(basename($filename), 3));
        if(!in_array($resoucename, array('')))
          {
          $apiResources[] = $module . '/'. $resoucename;
          }
        }
      }

    return $apiResources;
    }

  /**
   * This function is for getting the webapi methods defined in the API
   * component (in given module) of the implementing class.
   */
  public function getWebApiDocs($resource, $module = '')
    {
    $apiInfo = array();
    $apiComponent = MidasLoader::loadComponent('Api'.$resource, $module);
    $r = new ReflectionClass($apiComponent);
    $meths = $r->getMethods(ReflectionMethod::IS_PUBLIC);
    foreach($meths as $m)
      {
      $name = $m->getName();
      $docString = $m->getDocComment();
      $docString = trim($docString, '/');
      $docAttributes = explode('@', $docString);
      $return = '';
      $description = '';
      $http = '';
      $path = '';
      $resource = '';
      $idParam = 'id';
      $params = array();
      foreach($docAttributes as $docEntry)
        {
        $explodedDoc = explode('*', $docEntry);
        array_walk($explodedDoc,
                   create_function('&$val', '$val = trim($val);'));
        $doc = implode('', $explodedDoc);
        if(strpos($doc, 'param') === 0)
          {
          $splitParam = explode(' ', $doc);
          $paramName = trim($splitParam[1]);
          $paramValue = trim(implode(' ', array_slice($splitParam, 2)));
          $params[$paramName] = $paramValue;
          }
        elseif(strpos($doc, 'return') === 0)
          {
          $return = trim(substr($doc, 6));
          }
        elseif(strpos($doc, 'path') === 0)
          {
          $path = trim(substr($doc, 5));
          }
        elseif(strpos($doc, 'http') === 0)
          {
          $http = trim(substr($doc, 5));
          }
        elseif(strpos($doc, 'idparam') === 0)
          {
          $idParam = trim(substr($doc, 8));
          }
        else
          {
          $description = $doc;
          }
        }
      if(!empty($path))
        {
        $tokens = preg_split('@/@', $path, NULL, PREG_SPLIT_NO_EMPTY);
        $count = count($tokens);
        if(empty($module) & !empty($tokens)) // core
          {
          $resource = $module. '/' . $tokens[0];
          }
        else if(!empty($module) & count($tokens) > 1) // other modules
          {
          $resource = $module. '/' . $tokens[1];
          }
        }
      if(empty($resource))
        {
        continue;
        }
      $docs = array();
      if($idParam !== 'id')
        {
        $params['id'] = $params[$idParam];
        unset($params[$idParam]);
        }
      $docs['params'] = $params;
      $docs['return'] = $return;
      $docs['http'] = $http;
      $docs['path'] = $path;
      $docs['description'] = $description;
      $apiInfo[$resource][$name] = $docs;
      }

    return $apiInfo;
    }

  /**
   * This function is for getting the Swagger Api docs for a single model
   */
  public function getResourceApiDocs($resource, $module = '')
    {
    $apiInfo = $this->getWebApiDocs($resource, $module);
    $swaggerDoc = array();
    $swaggerDoc['apiVersion'] = '1.0';
    $swaggerDoc['swaggerVersion'] = '1.1';
    $webroot = Zend_Controller_Front::getInstance()->getRequest()->getBaseUrl();
    $swaggerDoc['basePath'] = $webroot.'/rest/';
    $useSessionParam = array(
      'name' => 'useSession',
      'paramType' => 'query',
      'required' => false,
      'description' =>  'Authenticate using the current Midas session',
      'allowMultiple' => false,
      'dataType' => 'string'
    );
    $tokenParam = array(
      'name' => 'token',
      'paramType' => 'query',
      'required' => false,
      'description' =>  'Authentication token',
      'allowMultiple' => false,
      'dataType' => 'string'
    );
    if(empty($module))
      {
      $swaggerDoc['resourcePath'] = '/'. $resource; // core apis
      }
    else
      {
      $swaggerDoc['resourcePath'] = '/'. $module . '/' . $resource; // module apis
      }
    $swaggerDoc['apis'] = array();
    if(key_exists($module.'/'.$resource, $apiInfo))
      {
      $resourceApiInfo = $apiInfo[$module.'/'.$resource];
      foreach($resourceApiInfo as $name => $docs)
        {
        $curApi = array();
        $curApi['path'] = $docs['path'];
        $curApi['operations'] = array();
        $operation = array();
        $operation['httpMethod'] = $docs['http'];
        $operation['summary'] = $docs['description'];
        $operation['notes'] = empty($docs['return']) ? '' : 'Return: ' . $docs['return'];
        $operation['nickname'] = $module . '_' . $name;
        $operation['responseClass'] = 'void';
        $operation['parameters'] = array();
        if($resource !== 'system')
          {
          array_push($operation['parameters'], $useSessionParam);
          array_push($operation['parameters'], $tokenParam);
          }
        foreach($docs['params'] as $paramName => $paramValue)
          {
          $param = array();
          $param['name'] = $paramName;
          if($paramName == 'id')
            {
            $param['paramType'] = 'path';
            }
          else
            {
            $param['paramType'] = 'query';
            }
          $param['required'] = true;
          $prefix = '(Optional)';
          if(substr($paramValue, 0, strlen($prefix)) == $prefix)
            {
            $paramValue = substr($paramValue, strlen($prefix), strlen($paramValue));
            $param['required'] = false;
            }
          $param['description'] = $paramValue;
          $param['allowMultiple'] = false;
          $param['dataType'] = 'string';
          array_push($operation['parameters'], $param);
          }
        array_push($curApi['operations'], $operation);
        array_push($swaggerDoc['apis'], $curApi);
        }
      }
    return $swaggerDoc;
    }

  } // end class
