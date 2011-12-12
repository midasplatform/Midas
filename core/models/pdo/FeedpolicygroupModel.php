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

require_once BASE_PATH.'/core/models/base/FeedpolicygroupModelBase.php';

/**
 * \class FeedpolicygroupModel
 * \brief Pdo Model
 */
class FeedpolicygroupModel extends FeedpolicygroupModelBase
{

  /** getPolicy
   * @return FeedpolicygroupDao
   */
  public function getPolicy($group, $feed)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$feed instanceof FeedDao)
      {
      throw new Zend_Exception("Should be a feed.");
      }
    return $this->initDao('Feedpolicygroup', $this->database->fetchRow($this->database->select()->where('feed_id = ?', $feed->getKey())->where('group_id = ?', $group->getKey())));
    }  // end getPolicy

} // end class
?>
