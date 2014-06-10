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

class Metadataextractor_ExtractorComponent extends AppComponent
  {
  /** extract metadata */
  public function extract($revision)
    {
    $itemRevisionModel = MidasLoader::loadModel("ItemRevision");
    $revision = $itemRevisionModel->load($revision['itemrevision_id']);
    if(!$revision)
      {
      return;
      }
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) != 1)
      {
      return;
      }
    $bitstream = $bitstreams[0];
    $ext = strtolower(substr(strrchr($bitstream->getName(), '.'), 1));

    $MetadataModel = MidasLoader::loadModel("Metadata");
    if($ext == 'pdf')
      {
      $pdf = Zend_Pdf::load($bitstream->getFullPath());
      foreach($pdf->properties as $name => $property)
        {
        $name = strtolower($name);
        try
          {
          $metadataDao = $MetadataModel->getMetadata(MIDAS_METADATA_TEXT, 'misc', $name);
          if(!$metadataDao)
            {
            $MetadataModel->addMetadata(MIDAS_METADATA_TEXT, 'misc', $name, '');
            }
          $MetadataModel->addMetadataValue($revision, MIDAS_METADATA_TEXT,
                       'misc', $name, $property);
          }
        catch (Zend_Exception $exc)
          {
          echo $exc->getMessage();
          }
        }
      }
    else
      {
      $modulesConfig=Zend_Registry::get('configsModules');
      $command = $modulesConfig['metadataextractor']->hachoir;
      exec(str_replace("'", '"',$command).' "'.$bitstream->getFullPath().'"', $output);

      if(!isset($output[0]) || $output[0] != "Metadata:")
        {
        return;
        }
      unset($output[0]);
      foreach($output as $out)
        {
        $out = substr($out, 2);
        $pos = strpos($out, ": ");
        $name = strtolower(substr($out, 0, $pos));
        $value = substr($out, $pos + 2);
        try
          {
          $metadataDao = $MetadataModel->getMetadata(MIDAS_METADATA_TEXT, 'misc', $name);
          if(!$metadataDao)
            {
            $MetadataModel->addMetadata(MIDAS_METADATA_TEXT, 'misc', $name, '');
            }
          $MetadataModel->addMetadataValue($revision, MIDAS_METADATA_TEXT,
               'misc', $name, $value);
          }
        catch (Zend_Exception $exc)
          {
          echo $exc->getMessage();
          }
        }
      }
    return;
    }

  } // end class
