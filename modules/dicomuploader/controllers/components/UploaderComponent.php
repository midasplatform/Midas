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
/** Uploade dicom files */
class Dicomuploader_UploaderComponent extends AppComponent
{

  /**
   * Verify that DICOM uploader is setup properly
   */
  public function isDICOMUploaderWorking()
  {
    $ret = array();
    $modulesConfig = Zend_Registry::get('configsModules');
    $dcm2xmlCommand = $modulesConfig['dicomuploader']->dcm2xml;
    $storescpCommand = $modulesConfig['dicomuploader']->storescp;
    $kwdicomextractorComponent = MidasLoader::loadComponent('Extractor', 'dicomextractor');
    $ret['dcm2xml'] = $kwdicomextractorComponent->getApplicationStatus($dcm2xmlCommand, 'dcm2xml');
    $ret['storescp'] = $kwdicomextractorComponent->getApplicationStatus($storescpCommand,
                                                   'storescp');
    $tmpDir= $modulesConfig['dicomuploader']->tmpdir;
    $ret['Uploader Temporary Folder Writable'] = array(is_writable($tmpDir));

    return $ret;
  }

} // end class
