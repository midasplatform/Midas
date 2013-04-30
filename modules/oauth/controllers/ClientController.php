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

/** Client management */
class Oauth_ClientController extends Oauth_AppController
{
  public $_models = array('User');
  public $_moduleModels = array('Client', 'Code', 'Token');

  /**
   * Shows the list of oauth clients owned by this user, and the list of authorized clients for this user.
   * This is a tab in the user's account page.
   * @param userId The id of the user
   */
  function indexAction()
    {
    $this->disableLayout();

    $userId = $this->_getParam('userId');
    if(!isset($userId))
      {
      throw new Zend_Exception('Must pass a client_id parameter', 400);
      }
    $user = $this->User->load($userId);
    if(!$user)
      {
      throw new Zend_Exception('Invalid userId', 404);
      }
    if(!$this->logged)
      {
      throw new Zend_Exception('Must be logged in', 403);
      }
    if(!$user->isAdmin() && $user->getKey() != $this->userSession->Dao->getKey())
      {
      throw new Zend_Exception('Admin permission required', 403);
      }
    $this->view->clients = $this->Oauth_Client->getByUser($user);
    $this->view->codes = $this->Oauth_Code->getByUser($user);
    $this->view->tokens = $this->Oauth_Token->getByUser($user, true);
    $this->view->user = $user;
    }

  /**
   * Create a new oauth client for the given user with the given name.
   * @param name Name of the client
   * @param userId Id of the user.  Must be self if session user is not administrator
   */
  function createAction()
    {
    $this->disableLayout();
    $this->disableView();

    $name = $this->_getParam('name');
    $userId = $this->_getParam('userId');
    if(!isset($userId))
      {
      throw new Zend_Exception('Must pass a userId parameter', 400);
      }
    $user = $this->User->load($userId);
    if(!$user)
      {
      throw new Zend_Exception('Invalid userId', 400);
      }
    if(!$this->logged)
      {
      throw new Zend_Exception('Must be logged in', 401);
      }
    if(!$user->isAdmin() && $user->getKey() != $this->userSession->Dao->getKey())
      {
      throw new Zend_Exception('Admin permission required', 403);
      }
    $name = trim($name);
    if(empty($name))
      {
      echo JsonComponent::encode(array('status' => 'error', 'message' => 'Name must not be empty'));
      return;
      }
    $clientDao = $this->Oauth_Client->create($user, $name);
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Client created', 'client' => $clientDao));
    }

  /**
   * Delete an oauth client
   * @param clientId The id of the client to delete
   */
  function deleteAction()
    {
    $this->disableLayout();
    $this->disableView();

    $clientId = $this->_getParam('clientId');
    if(!isset($clientId))
      {
      throw new Zend_Exception('Must pass a clientId parameter', 400);
      }
    $client = $this->Oauth_Client->load($clientId);
    if(!$client)
      {
      throw new Zend_Exception('Invalid clientId', 404);
      }

    if(!$this->logged)
      {
      throw new Zend_Exception('Must be logged in', 401);
      }
    if(!$this->userSession->Dao->isAdmin() && $client->getOwnerId() != $this->userSession->Dao->getKey())
      {
      throw new Zend_Exception('Admin permission required', 403);
      }

    $this->Oauth_Client->delete($client);
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Client deleted'));
    }
  } // end class
?>
