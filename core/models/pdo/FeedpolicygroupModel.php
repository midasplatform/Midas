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
