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
class Sizequota_ApiComponent extends AppComponent
{

  /** Return the user dao */
  private function _callModuleApiMethod($args, $coreApiMethod, $resource = null,  $hasReturn = true)
    {
    $ApiComponent = MidasLoader::loadComponent('Api'.$resource, 'sizequota');
    $rtn = $ApiComponent->$coreApiMethod($args);
    if($hasReturn)
      {
      return $rtn;
      }
    }

  /**
   * Get the size quota for a user.
   * @param user Id of the user to check
   * @return array('quota' => The size quota in bytes for the user, or empty string if unlimited,
                   'used' => Size in bytes currently used)
   */
  public function userGet($args)
    {
    return $this->_callModuleApiMethod($args, 'userGet', 'quota');
    }

  /**
   * Get the size quota for a community.
   * @param community Id of the community to check
   * @return array('quota' => The size quota in bytes for the community, or empty string if unlimited,
                   'used' => Size in bytes currently used)
   */
  public function communityGet($args)
    {
    return $this->_callModuleApiMethod($args, 'communityGet', 'quota');
    }

  /**
   * Set a quota for a folder. For MIDAS admin use only.
   * @param folder The folder id
   * @param quota (Optional) The quota. Pass a number of bytes or the empty string for unlimited.
     If this parameter isn't specified, deletes the current quota entry if one exists.
   */
  public function set($args)
    {
    return $this->_callModuleApiMethod($args, 'set', 'quota');
    }

}
