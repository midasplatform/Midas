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

require_once BASE_PATH.'/modules/remoteprocessing/models/base/WorkflowdomainPolicyuserModelBase.php';

/**
 * \class FolderpolicyuserModel
 * \brief Pdo Model
 */
class Remoteprocessing_WorkflowdomainPolicyuserModel extends Remoteprocessing_WorkflowdomainPolicyuserModelBase
{
  /** getPolicy
   * @return FolderpolicyuserDao
   */
  public function getPolicy($user, $workflowDomain)
    {
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    if(!$workflowDomain instanceof Remoteprocessing_WorkflowdomainDao)
      {
      throw new Zend_Exception("Should be a workflowDomain.");
      }
    return $this->initDao('WorkflowdomainPolicyuser', $this->database->fetchRow($this->database->select()
          ->where('workflowdomain_id = ?', $workflowDomain->getKey())
          ->where('user_id = ?', $user->getKey())
          ), 'remoteprocessing');
    }
}  // end class FolderpolicyuserModel
?>
