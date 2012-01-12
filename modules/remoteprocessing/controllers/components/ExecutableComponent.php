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

/** Web API Authentication Component */
class Remoteprocessing_ExecutableComponent extends AppComponent
{

  /** Constructor */
  function __construct()
    {
    }

  /** get Meta file*/
  function getMetaIoFile($itemDao)
    {
    $modelLoad = new MIDAS_ModelLoader();
    $itemModel = $modelLoad->loadModel('Item');
    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    $metaFile = false;
    foreach($bitstreams as $b)
      {
      if($b->getName() == 'MetaIO.vxml')
        {
        $metaFile = $b;
        break;
        }
      }
    return $metaFile;
    }

  /** get executable bitstream (return false if there is not) */
  function getExecutable($itemDao)
    {
    $executable = false;
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();
    foreach($bitstreams as $b)
      {
      if(is_executable($b->getFullPath()))
        {
        $executable = $b;
        }
      }
    return $executable;
    }

  /** schedule Job (create script and set parameters).*/
  function initAndSchedule($userDao, $executableItemDao, $jobName, $cmdOptions, $parametersList, $fire_time = false, $time_interval = false)
    {
    $componentLoader = new MIDAS_ComponentLoader();
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $jobComponent = $componentLoader->loadComponent('Job', 'remoteprocessing');

    if($time_interval === false)
      {
      $only_once = true;
      }
    else
      {
      $only_once = false;
      }

    $executable = $this->getExecutable($executableItemDao);

    if($executable == false)
      {
      throw new Zend_Exception('Unable to find executable');
      }

    $parameters['cmdOptions'] = $cmdOptions;
    $parameters['creator_id'] = $userDao->getKey();
    $parameters['job_name'] = $jobName;
    $parameters['parametersList'] = $parametersList;
    $parameters['executable'] = $executableItemDao->getKey();

    $ext = end(explode('.', $executable->getName()));
    if($ext == 'exe')
      {
      $os = MIDAS_REMOTEPROCESSING_OS_WINDOWS;
      }
    else
      {
      $os = MIDAS_REMOTEPROCESSING_OS_LINUX;
      }
    $jobParameters = $jobComponent->scheduleJob($parameters, '', $os, $fire_time, $time_interval, $only_once);
    }

  /** init job */
  public function processScheduledJobParameters($params)
    {
    $cmdOptions = $params['params']['cmdOptions'];
    $componentLoader = new MIDAS_ComponentLoader();
    $jobComponent = $componentLoader->loadComponent('Job', 'remoteprocessing');
    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $folderModel = $modelLoader->loadModel('Folder');
    $executable = $itemModel->load($params['params']['executable']);

    $params['params']['ouputFolders'] = array();
    $inputArray = array();
    $ouputArray = array();
    $inputArray[] = $executable;

    foreach($cmdOptions as $key => $option)
      {
      if($option['type'] == 'output')
        {
        $params['params']['ouputFolders'][] = $option['folderId'];
        $ouputArray[] = $option['fileName'];
        }
      elseif($option['type'] == 'input')
        {
        if(isset($option['folder']))
          {
          $folder = $folderModel->load($option['folder']);
          $items = $folder->getItems();
          foreach($items as $item)
            {
            $cmdOptions[$key]['item'][] = $item;
            }
          }
        foreach($cmdOptions[$key]['item'] as $item)
          {
          $inputArray[] = $item;
          }
        }
      }

    $commandMatrix = $this->_createParametersMatrix($cmdOptions);
    $tmp = $this->_createScript($params['params']['parametersList'], $commandMatrix, $executable, $ouputArray);

    $ouputArray = $tmp['outputArray'];
    $script = $tmp['script'];

    $params['params']['optionMatrix'] = $commandMatrix;

    $parameters = $jobComponent->initJobParameters('CALLBACK_REMOTEPROCESSING_EXECUTABLE_RESULTS', $inputArray, $ouputArray, $params['params']);
    return array('parameters' => $parameters, 'script' => $script);
    }

  /** create Script */
  private function _createScript($parametersList, $commandMatrix, $executable, $ouputArray)
    {
    $script = "#! /usr/bin/python\n";
    $script .= "import subprocess\n";
    $script .= "import time\n";
    foreach($commandMatrix as $key => $commandList)
      {
      $command = $executable->getName().' '.  join('', $commandList);
      $command = str_replace('{{key}}', '.'.$this->_generateSuffixOutputName($commandList, $parametersList).'.'.$key, $command);

      $script .= "start = time.clock()\n";
      $script .= "process = subprocess.Popen('".$command."', shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)\n";
      $script .= "process.wait()\n";
      $script .= "returnArray = process.communicate()\n";
      $script .= "end = time.clock()\n";
      $script .= "print '-COMMAND'\n";
      $script .= "print '".$command."'\n";
      $script .= "print '-EXECUTION TIME'\n";
      $script .= "print '%.2gs' % (end-start)\n";
      $script .= "print '-STDOUT'\n";
      $script .= "print returnArray[0]\n";
      $script .= "print '-STDERR'\n";
      $script .= "print returnArray[1]\n";
      }


    $tmpOutputArray = $ouputArray;
    foreach($tmpOutputArray as $ouput)
      {
      $ext = end(explode('.', $ouput));
      foreach($commandMatrix as $key => $commandList)
        {
        $ouputArray[] = str_replace('.'.$ext, '.'.$this->_generateSuffixOutputName($commandList, $parametersList).'.'.$key.'.'.$ext, $ouput);
        }
      }
    return array('script' => $script, 'outputArray' => $ouputArray);
    }

  /** generate suffix output name */
  private function _generateSuffixOutputName($commandList, $parametersList)
    {
    $return = "";
    foreach($commandList as $key => $command)
      {
      if(isset($parametersList[$key]) && !empty($parametersList[$key]))
        {
        $return = $return.substr($parametersList[$key], 0, 6)."-";
        $command = str_replace('"', '', $command);
        $command = (string)str_replace(' ', '', $command);
        $return = $return.$command."_";
        }
      }
    return substr($return, 0, -1);
    }
  /** create cmd option matrix*/
  private function _createParametersMatrix($cmdOptions)
    {
    $totalLine = 1;
    foreach($cmdOptions as $cmdOption)
      {
      if($cmdOption['type'] == 'param')
        {
        $totalLine = $totalLine * count($cmdOption['values']);
        }
      if($cmdOption['type'] == 'input')
        {
        $totalLine = $totalLine * count($cmdOption['item']);
        }
      }

    $matrix = array();
    $multipleElement = 1;
    foreach($cmdOptions as $key => $cmdOption)
      {
      $value = '';
      if(isset($cmdOption['tag']))
        {
        $value .= $cmdOption['tag'].' ';
        }

      if($cmdOption['type'] == 'input')
        {
        $values = $cmdOption['item'];
        $j = 0;
        for($i = 1; $i <= $totalLine; $i++)
          {
          $tmpvalue = $value.$values[$j]->getName().' ';
          if($i % $multipleElement == 0)
            {
            $j++;
            }
          if(!isset($values[$j]))
            {
            $j = 0;
            }
          $matrix[$i][$key] = $tmpvalue;
          }
        if(count($values) > 1)
          {
          $multipleElement = $multipleElement * count($values);
          }
        }
      elseif($cmdOption['type'] == 'output')
        {
        $ext = end(explode('.', $cmdOption['fileName']));
        $value .= '"'.  str_replace('.'.$ext, '{{key}}.'.$ext, $cmdOption['fileName']).'" ';
        for($i = 1; $i <= $totalLine; $i++)
          {
          $matrix[$i][$key] = $value;
          }
        }
      elseif($cmdOption['type'] == 'param')
        {
        $values = $cmdOption['values'];
        $j = 0;
        for($i = 1; $i <= $totalLine; $i++)
          {
          if(!isset($values[$j]))
            {
            $j = 0;
            }
          $tmpvalue = $value.$values[$j].' ';
          if($i % $multipleElement == 0)
            {
            $j++;
            }
          $matrix[$i][$key] = $tmpvalue;
          }
        if(count($values) > 1)
          {
          $multipleElement = $multipleElement * count($values);
          }
        }
      }
    return $matrix;
    }

  /** create Xml File */
  function createDefinitionFile($elements)
    {
    $xml = new SimpleXMLElement('<options></options>');
    $i = 0;
    // see javascript for the element array keys
    foreach($elements as $r)
      {
      $element = explode(';', $r);
      $option = $xml->addChild('option');
      $option->addChild('number', htmlspecialchars(utf8_encode($i)));
      $option->addChild('name', htmlspecialchars(utf8_encode($element[0])));
      $option->addChild('tag', htmlspecialchars(utf8_encode($element[5])));
      $option->addChild('longtag', htmlspecialchars(utf8_encode('')));
      $option->addChild('description', htmlspecialchars(utf8_encode('')));
      if($element[4] == 'True')
        {
        $option->addChild('required', htmlspecialchars(utf8_encode(1)));
        }
      else
        {
        $option->addChild('required', htmlspecialchars(utf8_encode(0)));
        }

      if($element[1] == 'ouputFile')
        {
        $option->addChild('channel', htmlspecialchars(utf8_encode('ouput')));
        }
      else
        {
        $option->addChild('channel', htmlspecialchars(utf8_encode('input')));
        }

      $option->addChild('nvalues', htmlspecialchars(utf8_encode(1)));

      $field = $option->addChild('field');
      $field->addChild('name', htmlspecialchars(utf8_encode($element[0])));
      $field->addChild('description', htmlspecialchars(utf8_encode('')));

      if($element[1] == 'inputParam')
        {
        $field->addChild('type', htmlspecialchars(utf8_encode($element[2])));
        }
      else
        {
        $field->addChild('type', htmlspecialchars(utf8_encode('string')));
        }
      $field->addChild('value', htmlspecialchars(utf8_encode('')));
      if($element[4] == 'True')
        {
        $field->addChild('required', htmlspecialchars(utf8_encode(1)));
        }
      else
        {
        $field->addChild('required', htmlspecialchars(utf8_encode(0)));
        }
      if($element[1] == 'inputParam')
        {
        $field->addChild('external', htmlspecialchars(utf8_encode(0)));
        }
      else
        {
        $field->addChild('external', htmlspecialchars(utf8_encode(1)));
        }
      }
    $xml = $xml->asXML();
    return $xml;
    }

}
