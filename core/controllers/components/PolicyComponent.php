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

/** Component containing helper methods to manage permissions */
class PolicyComponent extends AppComponent
  {

  var $Folder;
  var $Item;
  var $Folderpolicygroup;
  var $Folderpolicyuser;
  var $Itempolicygroup;
  var $Itempolicyuser;

  /** Constructor */
  function __construct()
    {
    $modelLoader = new MIDAS_ModelLoader();

    $this->Folder = $modelLoader->loadModel('Folder');
    $this->Item = $modelLoader->loadModel('Item');
    $this->Folderpolicygroup = $modelLoader->loadModel('Folderpolicygroup');
    $this->Folderpolicyuser = $modelLoader->loadModel('Folderpolicyuser');
    $this->Itempolicygroup = $modelLoader->loadModel('Itempolicygroup');
    $this->Itempolicyuser = $modelLoader->loadModel('Itempolicyuser');
    }

  /**
   * Copy the permissions from the given folder to all child folders and items. Do not pass a results
     parameter, that is for the recursive counting.
   * @param folder The folder dao
   * @param user The user dao
   * @return array('success' => number of resources whose policies were successfully changed,
                   'failure' => number of resources failed to change due to invalid permissions
   */
  function applyPoliciesRecursive($folder, $user, $results = array('success' => 0, 'failure' => 0))
    {
    foreach($folder->getFolders() as $subfolder)
      {
      if(!$this->Folder->policyCheck($subfolder, $user, MIDAS_POLICY_ADMIN))
        {
        $results['failure']++;
        continue;
        }
      // delete all existing policies on the subfolder
      foreach($subfolder->getFolderpolicygroup() as $folderPolicyGroup)
        {
        $this->Folderpolicygroup->delete($folderPolicyGroup);
        }
      foreach($subfolder->getFolderpolicyuser() as $folderPolicyUser)
        {
        $this->Folderpolicyuser->delete($folderPolicyUser);
        }

      // copy down policies from parent folder
      foreach($folder->getFolderpolicygroup() as $folderPolicyGroup)
        {
        $this->Folderpolicygroup->createPolicy($folderPolicyGroup->getGroup(), $subfolder, $folderPolicyGroup->getPolicy());
        }
      foreach($folder->getFolderpolicyuser() as $folderPolicyUser)
        {
        $this->Folderpolicyuser->createPolicy($folderPolicyUser->getUser(), $subfolder, $folderPolicyUser->getPolicy());
        }
      $results['success']++;
      $results = $this->applyPoliciesRecursive($subfolder, $user, $results);
      }

    foreach($folder->getItems() as $item)
      {
      if(!$this->Item->policyCheck($item, $user, MIDAS_POLICY_ADMIN))
        {
        $results['failure']++;
        continue;
        }
      // delete all existing policies on the item
      foreach($item->getItempolicygroup() as $itemPolicyGroup)
        {
        $this->Itempolicygroup->delete($itemPolicyGroup);
        }
      foreach($item->getItempolicyuser() as $itemPolicyUser)
        {
        $this->Itempolicyuser->delete($itemPolicyUser);
        }

      // copy down policies from parent folder
      foreach($folder->getFolderpolicygroup() as $folderPolicyGroup)
        {
        $this->Itempolicygroup->createPolicy($folderPolicyGroup->getGroup(), $item, $folderPolicyGroup->getPolicy());
        }
      foreach($folder->getFolderpolicyuser() as $folderPolicyUser)
        {
        $this->Itempolicyuser->createPolicy($folderPolicyUser->getUser(), $item, $folderPolicyUser->getPolicy());
        }
      $results['success']++;
      }
    return $results;
    }
  } // end class
