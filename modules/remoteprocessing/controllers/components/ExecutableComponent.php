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

  /** schedule Job (create script and set parameters).*/
  function initAndSchedule($itemDao, $xmlMeta, $javascriptResults)
    {
    $componentLoader = new MIDAS_ComponentLoader();
    $modelLoader = new MIDAS_ModelLoader();
    $folderModel = $modelLoader->loadModel('Folder');
    $itemModel = $modelLoader->loadModel('Item');
    $jobComponent = $componentLoader->loadComponent('Job', 'remoteprocessing');

    $inputArray = array();
    $ouputArray = array();
    $inputArray[] = $itemDao;
    $additionalParams = array('ouputFolders' => array());
    $i = 0;

    $revision = $itemModel->getLastRevision($itemDao);
    $bitstreams = $revision->getBitstreams();

    $executable = false;
    foreach($bitstreams as $b)
      {
      if(is_executable($b->getFullPath()))
        {
        $executable = $b;
        }
      }

    if($executable == false)
      {
      throw new Zend_Exception('Unable to find executable');
      }

    // Process parameters
    $isMultiParameter = false;
    $cmdOptions = array();
    foreach($xmlMeta->option as $option)
      {
      if(!isset($javascriptResults[$i]))
        {
        continue;
        }
      $result = $javascriptResults[$i];
      if($option->channel == 'ouput')
        {
        $resultArray = explode(";;", $result);
        $folder = $folderModel->load($resultArray[0]);
        if($folder == false)
          {
          throw new Zend_Exception('Unable to find folder');
          }
        $additionalParams['ouputFolders'][] = $resultArray[0];
        $ouputArray[] = $resultArray[1];
        $cmdOptions[$i] = array('type' => 'output', 'folderId' => $resultArray[0], 'fileName' => $resultArray[1]);
        }
      else if($option->field->external == 1)
        {
        $item = $itemModel->load($result);
        if($item == false)
          {
          throw new Zend_Exception('Unable to find item');
          }
        $inputArray[] = $item;
        $cmdOptions[$i] = array('type' => 'input', 'item' => $item);
        }
      else
        {
        $cmdOptions[$i] = array('type' => 'param', 'values' => array());
        if(strpos($result, ';') !== false)
          {
          $cmdOptions[$i]['values'] = explode(';', $result);
          }
        elseif(strpos($result, '-') !== false)
          {
          $tmpArray = explode('(', $result);
          if(count($tmpArray) == 1)
            {
            $step = 1;
            }
          else
            {
            $step = substr($tmpArray[1], 0, strlen($tmpArray[1])-1);
            }

          $tmpArray = explode('-', $tmpArray[0]);
          $start = $tmpArray[0];
          $end = $tmpArray[1];
          for($j = $start;$j <= $end;$j = $j + $step)
            {
            $cmdOptions[$i]['values'][] = $j;
            }
          }
        else
          {
          $cmdOptions[$i]['values'][] = $result;
          }

        if(!empty($option->tag))
          {
          $cmdOptions[$i]['tag'] = $option->tag;
          }
        }
      $i++;
      }


    $commandMatrix = $this->_createParametersMatrix($cmdOptions);

    $additionalParams['optionMatrix'] = $commandMatrix;

    $script = "#! /usr/bin/python\n";
    $script .= "import subprocess\n";
    foreach($commandMatrix as $key => $commandList)
      {
      $script .= "print 'Matrix Element ".$key."'\n";
      $command = $executable->getName().' '.  join('', $commandList);
      if($key == 1)
        {
        $command = str_replace('{{key}}', '', $command);
        }
      else
        {
        $command = str_replace('{{key}}', $key, $command);
        }

      $script .= "process = subprocess.Popen('".$command."', shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)\n";
      $script .= "process.wait()\n";
      $script .= "print process.stdout.readline()\n";
      $script .= "print process.stderr.readline()\n";
      }

    $tmpOutputArray = $ouputArray;
    foreach($tmpOutputArray as $ouput)
      {
      $ext = end(explode('.', $ouput));
      foreach($commandMatrix as $key => $commandList)
        {
        $ouputArray[] = str_replace('.'.$ext, $key.'.'.$ext, $ouput);
        }
      }

    $parameters = $jobComponent->initJobParameters('CALLBACK_REMOTEPROCESSING_EXECUTABLE_RESULTS', $inputArray, $ouputArray , $additionalParams);

    $ext = end(explode('.', $executable->getName()));
    if($ext == 'exe')
      {
      $os = MIDAS_REMOTEPROCESSING_OS_WINDOWS;
      }
    else
      {
      $os = MIDAS_REMOTEPROCESSING_OS_LINUX;
      }
    $jobParameters = $jobComponent->scheduleJob($parameters, $script, $os);
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
        $value .= '"'.$cmdOption['item']->getName().'" ';
        }
      elseif($cmdOption['type'] == 'output')
        {
        $ext = end(explode('.', $cmdOption['fileName']));
        $value .= '"'.  str_replace('.'.$ext, '{{key}}.'.$ext, $cmdOption['fileName']).'" ';
        }

      if($cmdOption['type'] == 'param')
        {
        $values = $cmdOption['values'];
        $j = 0;
        for($i = 1; $i <= $totalLine; $i++)
          {
          $tmpvalue = $value.$values[$j].' ';
          if($i % $multipleElement == 0)
            {
            $j++;
            }
          $matrix[$i][$key] = $tmpvalue;
          }
        if(count($values) >1)
          {
          $multipleElement = $multipleElement * count($values);
          }
        }
      else
        {
        for($i = 1; $i <= $totalLine; $i++)
          {
          $matrix[$i][$key] = $value;
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
      $option->addChild('number',htmlspecialchars(utf8_encode($i)));
      $option->addChild('name',htmlspecialchars(utf8_encode($element[0])));
      $option->addChild('tag',htmlspecialchars(utf8_encode($element[5])));
      $option->addChild('longtag',htmlspecialchars(utf8_encode('')));
      $option->addChild('description',htmlspecialchars(utf8_encode('')));
      if($element[4] == 'True')
        {
        $option->addChild('required',htmlspecialchars(utf8_encode(1)));
        }
      else
        {
        $option->addChild('required',htmlspecialchars(utf8_encode(0)));
        }

      if($element[1] == 'ouputFile')
        {
        $option->addChild('channel',htmlspecialchars(utf8_encode('ouput')));
        }
      else
        {
        $option->addChild('channel',htmlspecialchars(utf8_encode('input')));
        }

      $option->addChild('nvalues',htmlspecialchars(utf8_encode(1)));

      $field = $option->addChild('field');
      $field->addChild('name',htmlspecialchars(utf8_encode($element[0])));
      $field->addChild('description',htmlspecialchars(utf8_encode('')));

      if($element[1] == 'inputParam')
        {
        $field->addChild('type',htmlspecialchars(utf8_encode($element[2])));
        }
      else
        {
        $field->addChild('type',htmlspecialchars(utf8_encode('string')));
        }
      $field->addChild('value',htmlspecialchars(utf8_encode('')));
      if($element[4] == 'True')
        {
        $field->addChild('required',htmlspecialchars(utf8_encode(1)));
        }
      else
        {
        $field->addChild('required',htmlspecialchars(utf8_encode(0)));
        }
      if($element[1] == 'inputParam')
        {
        $field->addChild('external',htmlspecialchars(utf8_encode(0)));
        }
      else
        {
        $field->addChild('external',htmlspecialchars(utf8_encode(1)));
        }
      }
    $xml = $xml->asXML();
    return $xml;
    }

}
