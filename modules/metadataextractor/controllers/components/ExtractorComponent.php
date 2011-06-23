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

class Metadataextractor_ExtractorComponent extends AppComponent
{ 
  /** extract metadata */
  public function extract($revision)
    {
    $modelLoader = new MIDAS_ModelLoader;
    $itemRevisionModel = $modelLoader->loadModel("ItemRevision");  
    $revision = $itemRevisionModel->load($revision['itemrevision_id']);
    $bitstreams = $revision->getBitstreams();
    if(count($bitstreams) != 1)
      {
      return;
      }
    $bitstream = $bitstreams[0];
    $ext = strtolower(substr(strrchr($bitstream->getName(), '.'), 1));
    
    $MetadataModel = $modelLoader->loadModel("Metadata");  
    if($ext == 'pdf')
      {
      $pdf = Zend_Pdf::load($bitstream->getFullPath());
      foreach($pdf->properties as $name => $property)
        {
        $name = strtolower($name);
        try
          {
          $metadataDao = $MetadataModel->getMetadata(MIDAS_METADATA_GLOBAL, 'misc', $name); 
          if(!$metadataDao)
            {
            $MetadataModel->addMetadata(MIDAS_METADATA_GLOBAL, 'misc', $name, '');
            }
          $MetadataModel->addMetadataValue($revision, MIDAS_METADATA_GLOBAL, 
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
      if($output[0] != "Metadata:")
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
          $metadataDao = $MetadataModel->getMetadata(MIDAS_METADATA_GLOBAL, 'misc', $name); 
          if(!$metadataDao)
            {
            $MetadataModel->addMetadata(MIDAS_METADATA_GLOBAL, 'misc', $name, '');
            }
          $MetadataModel->addMetadataValue($revision, MIDAS_METADATA_GLOBAL, 
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
?>