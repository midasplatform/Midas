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

/** Feed policy model base */
abstract class FeedpolicygroupModelBase extends AppModel
{
  /** Constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'feedpolicygroup';

    $this->_mainData = array(
        'feed_id' => array('type' => MIDAS_DATA),
        'group_id' => array('type' => MIDAS_DATA),
        'policy' => array('type' => MIDAS_DATA),
        'date' => array('type' => MIDAS_DATA),
        'feed' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Feed', 'parent_column' => 'feed_id', 'child_column' => 'feed_id'),
        'group' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Group', 'parent_column' => 'group_id', 'child_column' => 'group_id')
      );
    $this->initialize(); // required
    } // end __construct()

  /** Abstract functions */
  abstract function getPolicy($group, $feed);

  /** create a policy
   * @return FeedpolicygroupDao*/
  public function createPolicy($group, $feed, $policy)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be a feedDao.");
      }
    if(!is_numeric($policy))
      {
      throw new Zend_Exception("Should be a number.");
      }
    if(!$group->saved && !$feed->saved)
      {
      throw new Zend_Exception("Save the daos first.");
      }
    if($this->getPolicy($group, $feed) !== false)
      {
      $this->delete($this->getPolicy($group, $feed));
      }
    $this->loadDaoClass('FeedpolicygroupDao');
    $policyGroupDao = new FeedpolicygroupDao();
    $policyGroupDao->setGroupId($group->getGroupId());
    $policyGroupDao->setFeedId($feed->getFeedId());
    $policyGroupDao->setPolicy($policy);
    $this->save($policyGroupDao);
    return $policyGroupDao;
    } // end createPolicy

} // end class FeedpolicygroupModelBase