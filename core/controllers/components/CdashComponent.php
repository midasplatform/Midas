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

/** CDash Componenet */
class CDashComponent extends AppComponent
  {
  /** submit tests to dashboard*/
  public function submitToCdash($serverUrl, $projectName, $tests, $machineName = "Midas", $buildName = "Midas Build", $buildType = "Experimental")
    {
    if(strpos($serverUrl, "http") === false)
      {
      throw new Zend_Exception("Error url format");
      }
    if(!is_string($projectName) || !is_array($tests) || !is_string($machineName) || !is_string($buildName) || !is_scalar($buildType))
      {
      throw new Zend_Exception("Error parametors");
      }
    foreach($tests as $name => $test)
      {
      if(!is_string($name))
        {
        throw new Zend_Exception("test array keys should be the name of the test");
        }
      if(!isset($test['fullname']))
        {
        throw new Zend_Exception("Please set the name of the test.");
        }
      if(!isset($test['output']))
        {
        throw new Zend_Exception("Please set the ouput of the test.");
        }
      if($test['status'] != 'failed' && $test['status'] != 'passed')
        {
        throw new Zend_Exception("The status should be passed or failed");
        }
      }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <Site BuildName="'.$buildName.'"
              BuildStamp="'.date('Ymd').'-'.((int)date('G')*60 + (int)date('i')).'-'.$buildType.'"
              Name="'.$machineName.'"
              Generator=""
              CompilerName=""
              OSName=""
              Hostname=""
              OSRelease=""
              OSVersion=""
              OSPlatform=""
              Is64Bits=""
              VendorString=""
              VendorID=""
              FamilyID=""
              ModelID=""
              ProcessorCacheSize=""
              NumberOfLogicalCPU=""
              NumberOfPhysicalCPU=""
              TotalVirtualMemory=""
              TotalPhysicalMemory=""
              LogicalProcessorsPerPhysical=""
              ProcessorClockFrequency=""
            >
            <Testing>
            <StartDateTime>'.date('M m H:i T').'</StartDateTime>
            <StartTestTime>'.time().'</StartTestTime>
            <TestList>
            ';
    foreach($tests as $name => $test)
      {
      $xml .= '<Test>'.$name.'</Test>
        ';
      }
    $xml .='</TestList>
      ';
    foreach($tests as $name => $test)
      {
      $xml .= '
      <Test Status="'.$test['status'].'">
      <Name>'.$name.'</Name>
      <Path>.</Path>
      <FullName>'.$test['fullname'].'</FullName>
      <FullCommandLine></FullCommandLine>
        <Results>
          <NamedMeasurement type="text/string" name="Exit Code"><Value>0</Value></NamedMeasurement>
          <NamedMeasurement type="text/string" name="Exit Value"><Value>0</Value></NamedMeasurement>
          <NamedMeasurement type="numeric/double" name="Execution Time"><Value>0.1</Value></NamedMeasurement>
          <NamedMeasurement type="text/string" name="Completion Status"><Value>Completed</Value></NamedMeasurement>
          <NamedMeasurement type="text/string" name="Command Line"><Value></Value></NamedMeasurement>
          <Measurement>
            <Value><![CDATA['.$test['output'].']]></Value>
          </Measurement>
        </Results>
      </Test>';
      }
    $xml .='
        <EndDateTime>'.date('M m H:i T').'</EndDateTime>
          <EndTestTime>'.time().'</EndTestTime>
        <ElapsedMinutes>0</ElapsedMinutes></Testing>
        </Site>';

    $params = array('http' => array(
              'method' => 'PUT',
              'content' => $xml,
              'header' => array('Content-type": "text/xml"')
            ));

    $url = $serverUrl.'/submit.php?project='.htmlentities($projectName);

    $ctx = stream_context_create($params);
    $fp = fopen($url, 'rb', false, $ctx);
    if(!$fp)
      {
      throw new Zend_Exception("Problem with ".$url.", ".$php_errormsg);
      }
    $response = stream_get_contents($fp);
    if ($response === false)
      {
      throw new Zend_Exception("Problem reading data from ".$url.", ".$php_errormsg);
      }
    }
  } // end class
