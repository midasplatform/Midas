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
class Solr_ApiComponent extends AppComponent
{
 /** Return the user dao */
  private function _callModuleApiMethod($args, $coreApiMethod, $resource = null,  $hasReturn = true)
    {
    $ApiComponent = MidasLoader::loadComponent('Api'.$resource, 'solr');
    $rtn = $ApiComponent->$coreApiMethod($args);
    if($hasReturn)
      {
      return $rtn;
      }
    }

  /**
   * Create a big thumbnail for the given bitstream with the given width. It is used as the main image of the given item and shown in the item view page.
   * @param token (Optional) Authentication token
   * @param query The Lucene search query
   * @param limit (Optional) The limit of the search; defaults to 25
   * @return The list of items matching the search query
   */
  public function searchAdvanced($args)
    {
    return $this->_callModuleApiMethod($args, 'searchAdvanced', 'search');
    }
} // end class
