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
include_once BASE_PATH . '/library/KWUtils.php';
/** Exract dicom metadata */
class Dicomextractor_ExtractorComponent extends AppComponent
  {

  /**
   * Check whether a given application is configured properly.
   * @param command the command to test with
   * @param appName the human-readable application name
   * @appendVersion whether we need the --version flag
   * @return an array indicating whether the app is valid or not
   */
  public function getApplicationStatus($preparedCommand, $appName, $appendVersion = true)
    {
    // Our config files replace double quotes with single quotes, so we need to fix that
    $preparedCommand = str_replace("'", '"', $preparedCommand);

    if($appendVersion)
      {
      $preparedCommand .= ' --version';
      }
    $this->prependDataDict($preparedCommand);
    exec($preparedCommand, $output, $return_var);
    if(empty($output))
      {
      return array(false, $appName.' was not found or is not configured properly.');
      }
    $parsedOutput = explode(' ', $output[0], 4);
    $appVersion = $parsedOutput[2];
    if($return_var !== 0)
      {
      return array(false, $appName.' was not found or is not configured properly.');
      }
    else
      {
      return array(true, $appName.' '.$appVersion . ' is present.');
      }
    }

  /**
   * Remove any params to the command, returning only the executable argument
   */
  private function getExecutableArg($commandWithParams)
    {
    $commandWithParams = trim($commandWithParams);
    // First test if the executable arugment has quotes
    $isQuoted = preg_match('/^["\'][^"]*["\']/', $commandWithParams, $matches);
    if($isQuoted)
      {
      $executable = $matches[0];
      }
    else
      {
      $commandWithParamsParts = explode(" ", $commandWithParams);
      $executable = $commandWithParamsParts[0];
      }
    return $executable;
    }

  /**
   * Prepend data dictionary environment variable if necessary.
   */
  private function prependDataDict(&$command)
    {
    $modulesConfig = Zend_Registry::get('configsModules');
    $dictPath = $modulesConfig['dicomextractor']->dcmdictpath;
    if($dictPath != "")
      {
      $command = 'DCMDICTPATH="'.$dictPath.'" '.$command;
      }
    }

  /**
   * Verify that DCMTK is setup properly
   */
  public function isDCMTKWorking()
    {
    $ret = array();
    $modulesConfig = Zend_Registry::get('configsModules');
    $dcm2xmlCommand = $modulesConfig['dicomextractor']->dcm2xml;
    $dcmftestCommand = $modulesConfig['dicomextractor']->dcmftest;
    // dcmj2pnmCommand may have some params that will cause it to throw
    // an error when no input is given, hence for existence and configuration
    // testing just get the command itself, without params
    $dcmj2pnmCommand = $this->getExecutableArg($modulesConfig['dicomextractor']->dcmj2pnm);
    $ret['dcm2xml'] = $this->getApplicationStatus($dcm2xmlCommand, 'dcm2xml');
    $ret['dcmftest'] = $this->getApplicationStatus($dcmftestCommand,
                                                   'dcmftest',
                                                   false);
    $ret['dcmj2pnm'] = $this->getApplicationStatus($dcmj2pnmCommand,
                                                   'dcmj2pnm');
    if($modulesConfig['dicomextractor']->dcmdictpath == "")
      {
      if(is_readable('/usr/local/share/dcmtk/dicom.dic') ||  // default on osx
        is_readable('/usr/share/dcmtk/dicom.dic'))  // default on ubuntu
        {
        $ret['dcmdatadict'] = array(true, 'DICOM Data Dictionary found at '.
          'default location.');
        }
      else
        {
        $ret['dcmdatadict'] = array(false, 'No DICOM Data dictionary set or '.
          'found. Please set the DCMDATADICT config variable.');
        }
      }
    else
      {
      $dataDictVar = $modulesConfig['dicomextractor']->dcmdictpath;
      $dictPaths = explode(":", $dataDictVar);
      $errorInDictVar = false;
      foreach($dictPaths as $path)
        {
        if(!is_readable($path))
          {
          $ret['dcmdatadict'] = array(false, 'Unable to find DICOM Data ' .
            'dictionary specified through the environment variable');
          $errorInDictVar = true;
          break;
          }
        }

      if(!$errorInDictVar)
        {
        $ret['dcmdatadict'] = array(true, 'DICOM Data Dictionary found as ' .
          'specified in the DCMDATADICT variable.');
        }
      }

    return $ret;
    }

  /**
   * Create a thumbnail from the series
   */
  public function thumbnail($item)
    {
    $itemModel = MidasLoader::loadModel("Item");
    $revision = $itemModel->getLastRevision($item);
    $bitstreams = $revision->getBitstreams();
    $numBitstreams = count($bitstreams);
    if($numBitstreams < 1)
      {
      return;
      }

    $thumbnailComponent = MidasLoader::loadComponent('Imagemagick',
                                                     'thumbnailcreator');
    $utilityComponent = MidasLoader::loadComponent('Utility');
    $bitstream = $bitstreams[$numBitstreams/2];

    // Turn the DICOM into a JPEG
    $modulesConfig = Zend_Registry::get('configsModules');
    $tempDirectory = $utilityComponent->getTempDirectory();
    $tmpSlice = $tempDirectory.'/'.$bitstream->getName().'.jpg';
    $command = $modulesConfig['dicomextractor']->dcmj2pnm;
    $preparedCommand = str_replace("'", '"',$command);
    $preparedCommand .= ' "'.$bitstream->getFullPath().'" "'.$tmpSlice.'"';
    $this->prependDataDict($preparedCommand);
    exec($preparedCommand, $output);

    // We have to spoof an item array for the thumbnail component. This
    // should certainly be fixed one day. It's a hack, but not my hack.
    $spoofedItem = array();
    $spoofedItem['item_id'] = $item->getKey();
    $thumbnailComponent->createThumbnail($spoofedItem,$tmpSlice);
    unlink($tmpSlice);
    }

  /** extract metadata
   *  HACK TODO FIXME Right now we only extract the metadata from the 0th
   *  bistream of the item. We should really do some sort of validation on
   *  the n bitstreams to make sure their tags match.
   */
  public function extract($revision)
    {
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) < 1)
      {
      return;
      }
    $bitstream = $bitstreams[0];
    $modulesConfig = Zend_Registry::get('configsModules');
    $command = $modulesConfig['dicomextractor']->dcm2xml;
    $preparedCommand = str_replace("'", '"',$command);
    $preparedCommand .= ' "'.$bitstream->getFullPath().'"';
    $this->prependDataDict($preparedCommand);
    exec($preparedCommand, $output);
    $xml = new XMLReader();
    $xml->xml(implode($output)); // implode our output
    $tagArray = array();
    $tagField = array();
    while($xml->read())
      {
      switch($xml->nodeType)
        {
        case XMLReader::END_ELEMENT:
          $tagField = array();
          break;
        case XMLReader::ELEMENT:
          if($xml->hasAttributes)
            {
            while($xml->moveToNextAttribute())
              {
              if($xml->name == 'tag')
                {
                $tagField['tag'] = $xml->value;
                }
              elseif($xml->name == 'name')
                {
                $tagField['name'] = $xml->value;
                }
              else
                {
                }
              }
            }
          break;
        case XMLReader::TEXT:
          $tagField['value'] = $xml->value;
          $tagArray[] = $tagField;
          break;
        }
      }
    $MetadataModel = MidasLoader::loadModel("Metadata");
    foreach($tagArray as $row)
      {
      try
        {
        $metadataDao = $MetadataModel->getMetadata(MIDAS_METADATA_TEXT,
                                                   'DICOM',
                                                   $row['name']);
        if(!$metadataDao)
          {
          $metadataDao = $MetadataModel->addMetadata(MIDAS_METADATA_TEXT,
                                                     'DICOM',
                                                     $row['name'],
                                                     $row['name']);
          }
        $metadataDao->setItemrevisionId($revision->getKey());
        $metadataDao->setValue($row['value']);
        if(!$MetadataModel->getMetadataValueExists($metadataDao))
          {
          $MetadataModel->addMetadataValue($revision, MIDAS_METADATA_TEXT,
                                           'DICOM',
                                           $row['name'],
                                           $row['value']);
          }
        }
      catch(Zend_Exception $exc)
        {
        echo $exc->getMessage();
        }
      }
    }

  } // end class
