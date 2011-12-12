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

/** ItempolicygroupModelBase */
class ItempolicygroupModelBase extends AppModel
{
  /** Constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'itempolicygroup';

    $this->_mainData = array(
        'item_id' => array('type' => MIDAS_DATA),
        'group_id' => array('type' => MIDAS_DATA),
        'policy' => array('type' => MIDAS_DATA),
        'date' => array('type' => MIDAS_DATA),
        'item' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Item', 'parent_column' => 'item_id', 'child_column' => 'item_id'),
        'group' => array('type' => MIDAS_MANY_TO_ONE, 'model' => 'Group', 'parent_column' => 'group_id', 'child_column' => 'group_id')
      );
    $this->initialize(); // required
    } // end __construct()


  /** delete */
  public function delete($dao)
    {
    $item = $dao->getItem();
    parent::delete($dao);
    $this->computePolicyStatus($item);
    }//end delete


  /** compute policy status*/
  public function computePolicyStatus($item)
    {
    $groupPolicies = $item->getItempolicygroup();
    $userPolicies = $item->getItempolicyuser();

    $shared = false;
    $modelLoad = new MIDAS_ModelLoader();
    $itemModel = $modelLoad->loadModel('Item');

    foreach($groupPolicies as $key => $policy)
      {
      if($policy->getGroupId() == MIDAS_GROUP_ANONYMOUS_KEY)
        {
        $item->setPrivacyStatus(MIDAS_PRIVACY_PUBLIC);
        $itemModel->save($item);
        return MIDAS_PRIVACY_PUBLIC;
        }
      else
        {
        $shared = true;
        }
      }
    foreach($userPolicies as $key => $policy)
      {
      if($policy->getPolicy() != MIDAS_POLICY_ADMIN)
        {
        $shared = true;
        break;
        }
      }

    if($shared)
      {
      $item->setPrivacyStatus(MIDAS_PRIVACY_SHARED);
      $itemModel->save($item);
      return MIDAS_PRIVACY_SHARED;
      }
    else
      {
      $item->setPrivacyStatus(MIDAS_PRIVACY_PRIVATE);
      $itemModel->save($item);
      return MIDAS_PRIVACY_PRIVATE;
      }
    }// end computePolicyStatus

} // end class ItempolicygroupModelBase