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

class Dicomextractor_ExtractorComponent extends AppComponent
{
  /** extract metadata
   *  HACK TODO FIXME Right now we only extract the metadata from the 0th
   *  bistream of the item. We should really do some sort of validation on
   *  the n bitstreams to make sure their tags match.
   */
  public function extract($revision)
    {
    $modelLoader = new MIDAS_ModelLoader;
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) < 1)
      {
      return;
      }
    $bitstream = $bitstreams[0];
    $modulesConfig=Zend_Registry::get('configsModules');
    $command = $modulesConfig['dicomextractor']->dcm2xml;
    $preparedCommand = str_replace("'", '"',$command);
    $preparedCommand .= ' "'.$bitstream->getFullPath().'"';
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
    $MetadataModel = $modelLoader->loadModel("Metadata");  
    foreach($tagArray as $row)
      {
      try
        {
        $metadataDao = $MetadataModel->getMetadata(MIDAS_METADATA_GLOBAL,
                                                   'DICOM',
                                                   $row['name']);
        if(!$metadataDao)
          {
          $MetadataModel->addMetadata(MIDAS_METADATA_GLOBAL,
                                      'DICOM',
                                      $row['name'],
                                      '');
          }
        $MetadataModel->addMetadataValue($revision, MIDAS_METADATA_GLOBAL,
                                         'DICOM',
                                         $row['name'],
                                         $row['value']);
        }
      catch (Zend_Exception $exc)
        {
        echo $exc->getMessage();
        }
      }
    }
    
} // end class
