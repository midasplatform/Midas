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

require_once BASE_PATH.'/library/KWUtils.php';

/**
 * ExportComponent.
 *
 * This class handles exporting bitstreams out of assetstores via creating symbolic links
 */
class ExportComponent extends AppComponent
{
    /**
     * Helper function to create a directory for an item.
     *
     * @param string $directorypath Directory to be created
     * @throws Zend_Exception
     */
    private function _createItemDirectory($directorypath)
    {
        // if the directory exists, try to delete it first
        if (file_exists($directorypath)) {
            if (!KWUtils::recursiveRemoveDirectory($directorypath)) {
                throw new Zend_Exception($directorypath.' has already existed and we cannot delete it.');
            }
        }
        if (!mkdir($directorypath)) {
            throw new Zend_Exception('Cannot create directory: '.$directorypath);
        }
        chmod($directorypath, 0777);
    }

    /**
     * Export bitstreams to target directory.
     *
     * Given itemIds, do policy check on these itemIds,
     * then create symbolic links to bitstreams (or copy the bitstreams)
     * in the "{targetDir}/{itemId}/" directories. If the {itemId} subdirectory
     * has been existed, delete the existing one first.
     * For policy check, we only check if the items are readable by the given user,
     * and don't further distinguish among "can_read", "can_write", and "owner"
     * levels.
     *
     * @param UserDao $userDao
     * @param string $targetDir Target directory to export bitstreams
     * @param array $itemIds Array of itemIds.
     *                               Each element is a comma separated value,
     *                               the 1st column is the actual item_id,
     *                               the 2nd is revision_number (optional)
     * @param bool $shouldSymLink Should we create symbolic links?
     *                               If not, the bitstreams will be copied to the target directory
     * @throws Zend_Exception
     */
    public function exportBitstreams($userDao, $targetDir, $itemIds, $shouldSymLink)
    {
        // if the path has a slash at the end, remove it here
        $targetDir = rtrim($targetDir, '/');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        // Get items
        $revisions = array();
        if (!is_array($itemIds)) {
            throw new Zend_Exception("Input parameter \$itemIds should be an array.");
        }
        if (!empty($itemIds)) {
            foreach ($itemIds as $itemId) {
                // $itemId is a comma separated value,
                // the 1st column is the actual item_id, the 2nd is revision_num (optional)
                $tmpId = explode(',', $itemId);
                if (empty($tmpId[0])) {
                    continue;
                }
                // delete the itemId directory if it exists which means it was exported by
                // other user before
                $item_export_dir = $targetDir.'/'.$itemId;
                if (file_exists($item_export_dir)) {
                    if (!KWUtils::recursiveRemoveDirectory($item_export_dir)) {
                        throw new Zend_Exception($item_export_dir.' has already existed and we cannot delete it.');
                    }
                }
                $item = $itemModel->load($tmpId[0]);
                if ($item == false) {
                    throw new Zend_Exception('Item '.$tmpId[0].' does not exist. Please check your input.');
                } elseif (!$itemModel->policyCheck($item, $userDao)) {
                    // Do policy check in the ITEM level, ignore items which cannot be exported by the user.
                    continue;
                }
                // Use the given revision_number if it is not empty
                if (isset($tmpId[1])) {
                    $revision = $itemModel->getRevision($item, $tmpId[1]);
                    if ($revision !== false) {
                        $revisions[] = $revision;
                    } else {
                        throw new Zend_Exception(
                            'Revision number '.$tmpId[1].' for item '.$tmpId[0].' does not exist. Please check your input.'
                        );
                    }
                } else {
                    // Otherwise use the latest revision
                    $revision = $itemModel->getLastRevision($item);
                    if ($revision !== false) {
                        $revisions[] = $revision;
                    }
                }
            }
        }

        // process the items which pass the ITEM level policy check
        if (!empty($revisions)) {
            /** @var RandomComponent $randomComponent */
            $randomComponent = MidasLoader::loadComponent('Random');

            foreach ($revisions as $revision) {
                $itemId = $revision->getItemId();
                $this->_createItemDirectory($targetDir.'/'.$itemId);
                // itemRevision -> bitstream is a one-to-many relation (in bitstream table)
                $bitstreams = $revision->getBitstreams();
                if (!empty($bitstreams)) {
                    foreach ($bitstreams as $bitstream) {
                        // if the bitstream is not an actual file, such as url type, skip it
                        if ($bitstream->getChecksum() == ' ') {
                            continue;
                        }
                        $source = $bitstream->getAssetstore()->getPath().'/'.$bitstream->getPath();
                        $dest = $targetDir.'/'.$itemId.'/'.$bitstream->getName();
                        // create symbolic links in target directory
                        if ($shouldSymLink) {
                            // for symbolic link option,if multiple bitstreams (in a single item revision)
                            // have the same file name, add a '.new' suffix to distinguish them
                            if (file_exists($dest)) {
                                $dest .= '.'.$randomComponent->generateInt().'.new';
                            }
                            if (!symlink($source, $dest)) {
                                throw new Zend_Exception('Cannot create symlink: '.$dest.'linked to'.$source);
                            }
                        } else {
                            // OR copy bitstreams to target directory
                            // for copy option, if multiple bitstreams (in a single item revision)
                            // have the same file name, new file(s) wil overwrite the existing file(s)
                            if (!copy($source, $dest)) {
                                throw new Zend_Exception('Cannot copy bitstream from: '.$source.'to: '.$dest);
                            }
                        }
                    }
                }
            }
        }
    }
}
