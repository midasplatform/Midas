<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

/**
 * Notification class that allows the web api to be automatically
 * initialized.
 */
class ApiEnabled_Notification extends MIDAS_Notification
  {

  /**
   * This function is for getting the webapi methods defined in the API
   * component of the implementing class. To enable this add the following
   * line to your init function.
   *
   * $this->enableWebAPI();
   *
   */
  public function getWebApiMethods()
    {
    $methods = array();
    $r = new ReflectionClass($this->ModuleComponent->Api);
    $meths = $r->getMethods(ReflectionMethod::IS_PUBLIC);
    foreach($meths as $m)
      {
      if(strpos($m->getDeclaringClass()->getName(), 'ApiComponent'))
        {
        $realName = $m->getName();
        $docString = $m->getDocComment();
        $docString = trim($docString, '/');
        $docAttributes = explode('@', $docString);
        $return = '';
        $description = '';
        $params = array();
        $example = array();
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
          else
            {
            $description = $doc;
            }
          }
        $name = strtolower($realName);
        $help = array();
        $help['params'] = $params;
        $help['example'] = $example;
        $help['return'] = $return;
        $help['description'] = $description;
        $methods[] = array('name' => $name,
                           'help' => $help,
                           'callbackObject' => &$this->ModuleComponent->Api,
                           'callbackFunction' => $realName);
        }
      }
    return $methods;
    }

  /**
   * Add to your init function to enable the web api for your module. This will
   * work provided you've created an ApiComponent.
   */
  public function enableWebAPI()
    {
    $this->addCallBack('CALLBACK_API_METHODS', 'getWebApiMethods');
    }
}
