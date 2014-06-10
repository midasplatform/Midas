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

/** Component for api methods */
class Dicomextractor_ApiComponent extends AppComponent
{

  /** Return the user dao */
  private function _callModuleApiMethod($args, $coreApiMethod, $resource = null,  $hasReturn = true)
    {
    $ApiComponent = MidasLoader::loadComponent('Api'.$resource, 'dicomextractor');
    $rtn = $ApiComponent->$coreApiMethod($args);
    if($hasReturn)
      {
      return $rtn;
      }
    }

  /**
   * Extract the dicom metadata from a revision
   * @param item the id of the item to be extracted
   * @return the id of the revision
   */
  function extract($args)
  {
    $ApihelperComponent = MidasLoader::loadComponent('Apihelper');
    $ApihelperComponent->renameParamKey($args, 'item', 'id');
    return $this->_callModuleApiMethod($args, 'extract', 'item');
  }

}
