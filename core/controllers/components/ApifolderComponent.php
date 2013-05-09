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

/** These are the implementations of the web api methods for folder */
class ApifolderComponent extends AppComponent
  {
  /**
   * Create a folder or update an existing one if one exists by the uuid passed.
   * If a folder is requested to be created with the same parentid and name as
   * an existing folder, an exception will be thrown and no new folder will
   * be created.
   * @path /folder
   * @http POST
   * @param name The name of the folder to create
   * @param description (Optional) The description of the folder
   * @param uuid (Optional) Uuid of the folder. If none is passed, will generate one.
   * @param privacy (Optional) Possible values [Public|Private]. Default behavior is to inherit from parent folder.
   * @param reuseExisting (Optional) If this parameter is set, will just return the existing folder if there is one with the name provided
   * @param parentid The id of the parent folder. Set this to -1 to create a top level user folder.
   * @return The folder object that was created
   */
  function folderCreate($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('name'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));
    $userDao = $apihelperComponent->getUser($args);
    if($userDao == false)
      {
      throw new Exception('Cannot create folder anonymously', MIDAS_INVALID_POLICY);
      }

    $folderModel = MidasLoader::loadModel('Folder');
    $name = $args['name'];
    $description = isset($args['description']) ? $args['description'] : '';

    $uuid = isset($args['uuid']) ? $args['uuid'] : '';
    $record = false;
    if(!empty($uuid))
      {
      $uuidComponent = MidasLoader::loadComponent('Uuid');
      $record = $uuidComponent->getByUid($uuid);
      }
    if($record != false && $record instanceof FolderDao)
      {
      if(!$folderModel->policyCheck($record, $userDao, MIDAS_POLICY_WRITE))
        {
        throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
        }
      $record->setName($name);
      if(isset($args['description']))
        {
        $record->setDescription($args['description']);
        }
      if(isset($args['privacy']))
        {
        if(!$folderModel->policyCheck($record, $userDao, MIDAS_POLICY_ADMIN))
          {
          throw new Exception('Folder Admin privileges required to set privacy', MIDAS_INVALID_POLICY);
          }
        $privacyCode = $apihelperComponent->getValidPrivacyCode($args['privacy']);
        $apihelperComponent->setFolderPrivacy($record, $privacyCode);
        }
      $folderModel->save($record);
      return $record->toArray();
      }
    else
      {
      if(!array_key_exists('parentid', $args))
        {
        throw new Exception('Parameter parentid is not defined', MIDAS_INVALID_PARAMETER);
        }
      if($args['parentid'] == -1) //top level user folder being created
        {
        $new_folder = $folderModel->createFolder($name, $description, $userDao->getFolderId(), $uuid);
        }
      else //child of existing folder
        {
        $folder = $folderModel->load($args['parentid']);
        if($folder == false)
          {
          throw new Exception('Parent doesn\'t exist', MIDAS_INVALID_PARAMETER);
          }
        if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_WRITE))
          {
          throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
          }
        if(($existing = $folderModel->getFolderExists($name, $folder)))
          {
          if(array_key_exists('reuseExisting', $args))
            {
            return $existing->toArray();
            }
          else
            {
            throw new Exception('A folder already exists in that parent with that name. Pass reuseExisting to reuse it.',
              MIDAS_INVALID_PARAMETER);
            }
          }
        $new_folder = $folderModel->createFolder($name, $description, $folder, $uuid);
        if($new_folder === false)
          {
          throw new Exception('Create folder failed', MIDAS_INTERNAL_ERROR);
          }
        $policyGroup = $folder->getFolderpolicygroup();
        $policyUser = $folder->getFolderpolicyuser();
        $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');
        $folderpolicyuserModel = MidasLoader::loadModel('Folderpolicyuser');
        foreach($policyGroup as $policy)
          {
          $folderpolicygroupModel->createPolicy($policy->getGroup(), $new_folder, $policy->getPolicy());
          }
        foreach($policyUser as $policy)
          {
          $folderpolicyuserModel->createPolicy($policy->getUser(), $new_folder, $policy->getPolicy());
          }
        if(!$folderModel->policyCheck($new_folder, $userDao, MIDAS_POLICY_ADMIN))
          {
          $folderpolicyuserModel->createPolicy($userDao, $new_folder, MIDAS_POLICY_ADMIN);
          }
        }

      // set privacy if desired
      if(isset($args['privacy']))
        {
        $privacyCode = $apihelperComponent->getValidPrivacyCode($args['privacy']);
        $apihelperComponent->setFolderPrivacy($new_folder, $privacyCode);
        }

      // reload folder to get up to date privacy status
      $new_folder = $folderModel->load($new_folder->getFolderId());
      return $new_folder->toArray();
      }
    }

  /**
   * Move a folder to the destination folder
   * @path /folder/move/{id}
   * @http PUT
   * @param id The id of the folder
   * @param dstfolderid The id of destination folder (new parent folder) where the folder is moved to
   * @return The folder object
   */
  function folderMove($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'dstfolderid'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');
    $id = $args['id'];
    $folder = $folderModel->load($id);
    $dstFolderId = $args['dstfolderid'];
    $dstFolder = $folderModel->load($dstFolderId);

    if($folder === false || !$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN)
      || !$folderModel->policyCheck($dstFolder, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("This folder doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }
    if($dstFolder == false)
      {
      throw new Exception("Unable to load destination folder.", MIDAS_INVALID_POLICY);
      }
    $folderModel->move($folder, $dstFolder);

    $folder = $folderModel->load($id);
    return $folder->toArray();
    }

  /**
   * Get information about the folder
   * @path /folder/{id}
   * @http GET
   * @param id The id of the folder
   * @return The folder object, including its parent object
   */
  function folderGet($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');

    $id = $args['id'];
    $folder = $folderModel->load($id);

    if($folder === false || !$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This folder doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $arr = $folder->toArray();
    $arr['parent'] = $folder->getParent();
    return $arr;
    }

  /**
   * Wrapper function for cleaning output of folderGet
   */
  function folderGetWrapper($args)
    {
    $in = $this->folderGet($args);
    $out = array();
    $out['id'] = $in['folder_id'];
    $out['parent_folder_id'] = $in['parent_id'];
    $out['name'] = $in['name'];
    $out['description'] = $in['description'];
    $out['date_created'] = $in['date_creation'];
    $out['date_updated'] = $in['date_update'];
    $out['views'] = $in['view'];
    $out['public'] = $in['privacy_status'] == 0;
    $out['uuid'] = $in['uuid'];
    return $out;
    }

  /**
   * List the permissions on a folder, requires Admin access to the folder.
   * @path /folder/permission/{id}
   * @http GET
   * @param id The id of the folder
   * @return A list with three keys: privacy, user, group; privacy will be the
     folder's privacy string [Public|Private]; user will be a list of
     (user_id, policy, email); group will be a list of (group_id, policy, name).
     policy for user and group will be a policy string [Admin|Write|Read].
   */
  public function folderListPermissions($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');
    $folderModel = MidasLoader::loadModel('Folder');
    $folderId = $args['id'];
    $folder = $folderModel->load($folderId);

    if($folder === false)
      {
      throw new Exception("This folder doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the folder to list permissions.", MIDAS_INVALID_POLICY);
      }

    return $apihelperComponent->listResourcePermissions($folderpolicygroupModel->computePolicyStatus($folder), $folder->getFolderpolicyuser(),  $folder->getFolderpolicygroup());

    }

  /**
   * Get the immediate children of a folder (non-recursive)
   * @path /folder/children/{id}
   * @http GET
   * @param id The id of the folder
   * @return The items and folders in the given folder
   */
  function folderChildren($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));
    $userDao = $apihelperComponent->getUser($args);

    $id = $args['id'];
    $folderModel = MidasLoader::loadModel('Folder');
    $folder = $folderModel->load($id);

    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
    try
      {
      $folders = $folderModel->getChildrenFoldersFiltered($folder, $userDao);
      $items = $folderModel->getItemsFiltered($folder, $userDao);
      }
    catch(Exception $e)
      {
      throw new Exception($e->getMessage(), MIDAS_INTERNAL_ERROR);
      }
    $itemsList = array();
    foreach($items as $item)
      {
      $itemArray = $item->toArray();
      $itemArray['extraFields'] = $apihelperComponent->getItemExtraFields($item);
      $itemsList[] = $itemArray;
      }

    return array('folders' => $folders, 'items' => $itemsList);
    }

  /**
   * Set the privacy status on a folder, and push this value down recursively
     to all children folders and items, requires Admin access to the folder.
   * @path /folder/setprivacyrecursive/{id}
   * @http PUT
   * @param id The id of the folder.
   * @param privacy Desired privacy status, one of [Public|Private].
   * @return An array with keys 'success' and 'failure' indicating a count
     of children resources that succeeded or failed the permission change.
   */
  function folderSetPrivacyRecursive($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'privacy'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');
    $folderId = $args['id'];
    $folder = $folderModel->load($folderId);

    if($folder === false)
      {
      throw new Exception("This folder doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the folder to set privacy.", MIDAS_INVALID_POLICY);
      }

    $privacyCode = $apihelperComponent->getValidPrivacyCode($args['privacy']);
    $apihelperComponent->setFolderPrivacy($folder, $privacyCode);

    // now push down the privacy recursively
    $policyComponent = MidasLoader::loadComponent('Policy');
    // send a null Progress since we aren't interested in progress
    // prepopulate results with 1 success for the folder we have already changed
    $results = $policyComponent->applyPoliciesRecursive($folder, $userDao, null, $results = array('success' => 1, 'failure' => 0));
    return $results;
    }

  /**
   * Add a folderpolicygroup to a folder with the passed in group and policy;
     if a folderpolicygroup exists for that group and folder, it will be replaced
     with the passed in policy.
   * @path /folder/addpolicygroup/{id}
   * @http PUT
   * @param id The id of the folder.
   * @param group_id The id of the group.
   * @param policy Desired policy status, one of [Admin|Write|Read].
   * @param recursive If included will push all policies from
     the passed in folder down to its child folders and items, default is non-recursive.
   * @return An array with keys 'success' and 'failure' indicating a count of
     resources affected by the addition.
   */
  function folderAddPolicygroup($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'group_id', 'policy'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');
    $folderId = $args['id'];
    $folder = $folderModel->load($folderId);
    if($folder === false)
      {
      throw new Exception("This folder doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the folder.", MIDAS_INVALID_POLICY);
      }

    $groupModel = MidasLoader::loadModel('Group');
    $group = $groupModel->load($args['group_id']);
    if($group === false)
      {
      throw new Exception("This group doesn't exist.", MIDAS_INVALID_PARAMETER);
      }

    $policyCode = $apihelperComponent->getValidPolicyCode($args['policy']);

    $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');
    $folderpolicygroupModel->createPolicy($group, $folder, $policyCode);

    // we have now changed 1 folder successfully
    $results = array('success' => 1, 'failure' => 0);

    if(isset($args['recursive']))
      {
      // now push down the privacy recursively
      $policyComponent = MidasLoader::loadComponent('Policy');
      // send a null Progress since we aren't interested in progress
      $results = $policyComponent->applyPoliciesRecursive($folder, $userDao, null, $results);
      }

    return $results;
    }

  /**
   * Remove a folderpolicygroup from a folder with the passed in group if the
     folderpolicygroup exists.
   * @path /folder/removepolicygroup/{id}
   * @http PUT
   * @param id The id of the folder.
   * @param group_id The id of the group.
   * @param recursive If included will push all policies after the removal from
     the passed in folder down to its child folders and items, default is non-recursive.
   * @return An array with keys 'success' and 'failure' indicating a count of
     resources affected by the removal.
   */
  function folderRemovePolicygroup($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'group_id'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');
    $folderId = $args['id'];
    $folder = $folderModel->load($folderId);
    if($folder === false)
      {
      throw new Exception("This folder doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the folder.", MIDAS_INVALID_POLICY);
      }

    $groupModel = MidasLoader::loadModel('Group');
    $group = $groupModel->load($args['group_id']);
    if($group === false)
      {
      throw new Exception("This group doesn't exist.", MIDAS_INVALID_PARAMETER);
      }

    $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');
    $folderpolicygroup = $folderpolicygroupModel->getPolicy($group, $folder);
    if($folderpolicygroup !== false)
      {
      $folderpolicygroupModel->delete($folderpolicygroup);
      }

    // we have now changed 1 folder successfully
    $results = array('success' => 1, 'failure' => 0);

    if(isset($args['recursive']))
      {
      // now push down the privacy recursively
      $policyComponent = MidasLoader::loadComponent('Policy');
      // send a null Progress since we aren't interested in progress
      $results = $policyComponent->applyPoliciesRecursive($folder, $userDao, null, $results);
      }

    return $results;
    }

  /**
   * Add a folderpolicyuser to a folder with the passed in user and policy;
     if a folderpolicyuser exists for that user and folder, it will be replaced
     with the passed in policy.
   * @path /folder/addpolicyuser/{id}
   * @http PUT
   * @param id The id of the folder.
   * @param user_id The id of the targeted user to create the policy for.
   * @param policy Desired policy status, one of [Admin|Write|Read].
   * @param recursive If included will push all policies from
     the passed in folder down to its child folders and items, default is non-recursive.
   * @return An array with keys 'success' and 'failure' indicating a count of
     resources affected by the addition.
   */
  function folderAddPolicyuser($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'user_id', 'policy'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $adminUser = $apihelperComponent->getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');
    $folderId = $args['id'];
    $folder = $folderModel->load($folderId);
    if($folder === false)
      {
      throw new Exception("This folder doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$folderModel->policyCheck($folder, $adminUser, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the folder.", MIDAS_INVALID_POLICY);
      }

    $userModel = MidasLoader::loadModel('User');
    $targetUserId = $args['user_id'];
    $targetUser = $userModel->load($targetUserId);
    if($targetUser === false)
      {
      throw new Exception("This user doesn't exist.", MIDAS_INVALID_PARAMETER);
      }

    $policyCode = $apihelperComponent->getValidPolicyCode($args['policy']);

    $folderpolicyuserModel = MidasLoader::loadModel('Folderpolicyuser');
    $folderpolicyuserModel->createPolicy($targetUser, $folder, $policyCode);

    // we have now changed 1 folder successfully
    $results = array('success' => 1, 'failure' => 0);

    if(isset($args['recursive']))
      {
      // now push down the privacy recursively
      $policyComponent = MidasLoader::loadComponent('Policy');
      // send a null Progress since we aren't interested in progress
      $results = $policyComponent->applyPoliciesRecursive($folder, $adminUser, null, $results);
      }

    return $results;
    }

  /**
   * Remove a folderpolicyuser from a folder with the passed in user if the
     folderpolicyuser exists.
   * @path /folder/removepolicyuser/{id}
   * @http PUT
   * @param id The id of the folder.
   * @param user_id The id of the target user.
   * @param recursive If included will push all policies after the removal from
     the passed in folder down to its child folders and items, default is non-recursive.
   * @return An array with keys 'success' and 'failure' indicating a count of
     resources affected by the removal.
   */
  function folderRemovePolicyuser($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id', 'user_id'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $folderModel = MidasLoader::loadModel('Folder');
    $folderId = $args['id'];
    $folder = $folderModel->load($folderId);
    if($folder === false)
      {
      throw new Exception("This folder doesn't exist.", MIDAS_INVALID_PARAMETER);
      }
    if(!$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("Admin privileges required on the folder.", MIDAS_INVALID_POLICY);
      }

    $userModel = MidasLoader::loadModel('User');
    $user = $userModel->load($args['user_id']);
    if($user === false)
      {
      throw new Exception("This user doesn't exist.", MIDAS_INVALID_PARAMETER);
      }

    $folderpolicyuserModel = MidasLoader::loadModel('Folderpolicyuser');
    $folderpolicyuser = $folderpolicyuserModel->getPolicy($user, $folder);
    if($folderpolicyuser !== false)
      {
      $folderpolicyuserModel->delete($folderpolicyuser);
      }

    // we have now changed 1 folder successfully
    $results = array('success' => 1, 'failure' => 0);

    if(isset($args['recursive']))
      {
      // now push down the privacy recursively
      $policyComponent = MidasLoader::loadComponent('Policy');
      // send a null Progress since we aren't interested in progress
      $results = $policyComponent->applyPoliciesRecursive($folder, $userDao, null, $results);
      }

    return $results;
    }

  /**
   * Delete a folder. Requires admin privileges on the folder
   * @path /folder/{id}
   * @http DELETE
   * @param id The id of the folder
   */
  function folderDelete($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));

    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
    $userDao = $apihelperComponent->getUser($args);
    if($userDao == false)
      {
      throw new Exception('Unable to find user', MIDAS_INVALID_TOKEN);
      }
    $id = $args['id'];
    $folderModel = MidasLoader::loadModel('Folder');
    $folder = $folderModel->load($id);

    if($folder === false || !$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_ADMIN))
      {
      throw new Exception("This folder doesn't exist  or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $folderModel->delete($folder);
    }

  /**
   * Download a folder
   * @path /folder/download/{id}
   * @http GET
   * @param id The id of the folder
   * @return A zip archive of the folder's contents
   */
  function folderDownload($args)
    {
    $apihelperComponent = MidasLoader::loadComponent('Apihelper');
    $apihelperComponent->validateParams($args, array('id'));
    $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
    $userDao = $apihelperComponent->getUser($args);

    $id = $args['id'];
    $folderModel = MidasLoader::loadModel('Folder');
    $folder = $folderModel->load($id);

    if($folder === false || !$folderModel->policyCheck($folder, $userDao, MIDAS_POLICY_READ))
      {
      throw new Exception("This folder doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
      }

    $redirUrl = '/download/?folders='.$folder->getKey();
    if($userDao && array_key_exists('token', $args))
      {
      $redirUrl .= '&authToken='.$args['token'];
      }
    $r = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
    $r->gotoUrl($redirUrl);
    }
  } // end class
