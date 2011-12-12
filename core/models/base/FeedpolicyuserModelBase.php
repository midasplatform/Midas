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

/** FeedpolicyuserModelBase*/
abstract class FeedpolicyuserModelBase extends AppModel
{
  /** Contructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'feedpolicyuser';
    $this->_mainData = array(
        'feed_id' => array('type' => MIDAS_DATA),
        'user_id' => array('type' => MIDAS_DATA),
        'policy' => array('type' => MIDAS_DATA),
        'date' => array('type' => MIDAS_DATA),
        'feed' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Feed', 'parent_column' => 'feed_id', 'child_column' => 'feed_id'),
        'user' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'User', 'parent_column' => 'user_id', 'child_column' => 'user_id')
      );
    $this->initialize(); // required
    } // end __construct()

  /** Abstract functions */
  abstract function getPolicy($user, $feed);

  /** create a policy
   * @return FeedpolicyuserDao*/
  public function createPolicy($user, $feed, $policy)
    {
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be a feed.");
      }
    if(!is_numeric($policy))
      {
      throw new Zend_Exception("Should be a number.");
      }
    if(!$user->saved && !$feed->saved)
      {
      throw new Zend_Exception("Save the daos first.");
      }
    if($this->getPolicy($user, $feed) !== false)
      {
      $this->delete($this->getPolicy($user, $feed));
      }
    $this->loadDaoClass('FeedpolicyuserDao');
    $policyUser = new FeedpolicyuserDao();
    $policyUser->setUserId($user->getUserId());
    $policyUser->setFeedId($feed->getFeedId());
    $policyUser->setPolicy($policy);
    $this->save($policyUser);
    return $policyUser;
    } // end createPolicy

} // end class FeedpolicyuserModelBase
