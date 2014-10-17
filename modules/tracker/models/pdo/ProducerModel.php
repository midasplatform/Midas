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

require_once BASE_PATH.'/modules/tracker/models/base/ProducerModelBase.php';

/**
 * Producer PDO Model
 */
class Tracker_ProducerModel extends Tracker_ProducerModelBase
{
    /**
     * Return all producers for the given community
     */
    public function getByCommunityId($communityId)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->where('community_id = ?', $communityId);
        $rowset = $this->database->fetchAll($sql);
        $producers = array();
        foreach ($rowset as $row) {
            $producers[] = $this->initDao('Producer', $row, $this->moduleName);
        }

        return $producers;
    }

    /**
     * Return the producer with the given display name under the given community
     */
    public function getByCommunityIdAndName($communityId, $displayName)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->where('community_id = ?', $communityId)->where(
            'display_name = ?',
            $displayName
        );

        return $this->initDao('Producer', $this->database->fetchRow($sql), $this->moduleName);
    }
}
