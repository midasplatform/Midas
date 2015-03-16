<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

require_once BASE_PATH.'/modules/packages/models/base/PackageModelBase.php';

/**
 * Package PDO Model
 */
class Packages_PackageModel extends Packages_PackageModelBase
{
    /**
     * Return all the record in the table
     *
     * @param params Optional associative array specifying an 'os', 'arch', 'submissiontype' and 'packagetype'.
     * @return Array of package Daos
     */
    public function get(
        $params = array(
            'os' => 'any',
            'arch' => 'any',
            'submissiontype' => 'any',
            'packagetype' => 'any',
            'revision' => 'any',
            'productname' => 'any',
            'codebase' => 'any',
            'release' => 'any',
        )
    ) {
        $sql = $this->database->select();
        foreach (array(
                     'os',
                     'arch',
                     'submissiontype',
                     'packagetype',
                     'revision',
                     'productname',
                     'codebase',
                     'release',
                     'application_id',
                 ) as $option) {
            if (array_key_exists($option, $params) && $params[$option] != 'any'
            ) {
                $sql->where('packages_package.'.$option.' = ?', $params[$option]);
            }
        }
        if (array_key_exists('order', $params)) {
            $direction = array_key_exists('direction', $params) ? strtoupper($params['direction']) : 'ASC';
            $sql->order($params['order'].' '.$direction);
        }
        if (array_key_exists('limit', $params) && is_numeric($params['limit']) && $params['limit'] > 0
        ) {
            $sql->limit($params['limit']);
        }
        $rowset = $this->database->fetchAll($sql);
        $rowsetAnalysed = array();
        foreach ($rowset as $row) {
            $tmpDao = $this->initDao('Package', $row, 'packages');
            $rowsetAnalysed[] = $tmpDao;
        }

        return $rowsetAnalysed;
    }

    /** get all package records */
    public function getAll()
    {
        return $this->database->getAll('Package', 'packages');
    }

    /**
     * Return a package_Package dao based on an itemId.
     */
    public function getByItemId($itemId)
    {
        $sql = $this->database->select()->where('item_id = ?', $itemId);
        $row = $this->database->fetchRow($sql);
        $dao = $this->initDao('Package', $row, 'packages');

        return $dao;
    }

    /**
     * For the given os, arch, and application (and optionally submission type),
     * return the most recent package of each package type
     */
    public function getLatestOfEachPackageType($application, $os, $arch, $submissiontype = null)
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            'packages_package',
            array('packagetype')
        )->where(
            'application_id = ?',
            $application->getKey()
        )->where('os = ?', $os)->where('arch = ?', $arch)->distinct();
        if ($submissiontype) {
            $sql->where('submissiontype = ?', $submissiontype);
        }
        $rowset = $this->database->fetchAll($sql);
        $types = array();
        foreach ($rowset as $row) {
            $types[] = $row['packagetype'];
        }

        // For each distinct package type, get the most recent matching dao
        $latestPackages = array();
        foreach ($types as $type) {
            $sql = $this->database->select()->setIntegrityCheck(false)->where(
                'application_id = ?',
                $application->getKey()
            )->where(
                'os = ?',
                $os
            )->where('arch = ?', $arch)->where('packagetype = ?', $type)->order('checkoutdate DESC')->limit(1);
            if ($submissiontype) {
                $sql->where('submissiontype = ?', $submissiontype);
            }
            $row = $this->database->fetchRow($sql);
            $latestPackages[] = $this->initDao('Package', $row, 'packages');
        }

        return $latestPackages;
    }
}
