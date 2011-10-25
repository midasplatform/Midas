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
    $command = $executable->getName().' ';

    $isMultiParameter = false;
    foreach($xmlMeta->option as $option)
      {
      if(!isset($javascriptResults[$i]))
        {
        continue;
        }
      if(!empty($option->tag))
        {
        $command .= $option->tag." ";
        }
      $resut = $javascriptResults[$i];
      if($option->channel == 'ouput')
        {
        $resutArray = explode(";;", $resut);
        $folder = $folderModel->load($resutArray[0]);
        if($folder == false)
          {
          throw new Zend_Exception('Unable to find folder');
          }
        $additionalParams['ouputFolders'][] = $resutArray[0];
        $ouputArray[] = $resutArray[1];
        $command .= "'".$resutArray[1]."' ";
        }
      else if($option->field->external == 1)
        {
        $item = $itemModel->load($resut);
        if($item == false)
          {
          throw new Zend_Exception('Unable to find item');
          }
        $inputArray[] = $item;
        $command .= "'".$item->getName()."' ";
        }
      else
        {
        $command .= "".$resut." ";
        }
      $i++;
      }

    $script = "#! /usr/bin/python\n";
    $script .= "import subprocess\n";
    $script .= "process = subprocess.Popen(\"".$command."\", shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)\n";
    $script .= "process.wait()\n";
    $script .= "print process.stdout.readline()\n";
    $script .= "print process.stderr.readline()\n";

    $parameters = $jobComponent->initJobParameters('CALLBACK_REMOTEPROCESSING_EXECUTABLE_RESULTS', $inputArray, $ouputArray , $additionalParams);

    $jobParameters = $jobComponent->scheduleJob($parameters, $script, MIDAS_REMOTEPROCESSING_OS_WINDOWS);
    /*    $this->disableView();
    $componentLoader = new MIDAS_ComponentLoader();
    $jobComponent = $componentLoader->loadComponent('Job', 'remoteprocessing');

    $itemDao = $this->Item->load(115);

    $parameters = $jobComponent->initJobParameters('CALLBACK_ZEISS_RESULTS',array($itemDao), array('test.txt'));



    $jobParameters = $jobComponent->scheduleJob($parameters, $script, MIDAS_REMOTEPROCESSING_OS_WINDOWS);*/
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
