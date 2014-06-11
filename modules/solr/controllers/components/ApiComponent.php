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

/** Component for api methods */
class Solr_ApiComponent extends AppComponent
  {
 /** Return the user dao */
  private function _callModuleApiMethod($args, $coreApiMethod, $resource = null, $hasReturn = true)
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
