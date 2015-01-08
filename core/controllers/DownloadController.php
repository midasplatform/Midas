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

/**
 *  Controller for downloading elements.
 */
class DownloadController extends AppController
{
    /** @var array */
    public $_models = array('Folder', 'Item', 'Community', 'User', 'Bitstream');

    /** @var array */
    public $_components = array('DownloadBitstream');

    /**
     * Index action.
     *
     * @param folders = 12-13 (will download a zip of the folder 12 and 13, recursively)
     * @param folders = 12, 1-13, 1 (will download a zip of the folder 12 and 13, recursively) // Need testing
     * @param items = 12-13 (will download a zip containing the last revisions of the items 12 and 13)
     * @param items = 12, 1-13 (will download a zip containing the revision 1 of item 12 and last revision of item 13)
     * @param items = 12, 1 (will download the revision 1 of the item 12, a zip if there are multiple bitstream or simply the file)
     * @param bitstream = 1 (will download related bitstream)
     * @param offset The offset in bytes if downloading a bitstream (defaults to 0)
     * @param name Alternate filename when downloading a bitstream (defaults to bitstream name)
     * @throws Zend_Exception
     */
    public function indexAction()
    {
        $this->disableLayout();
        $itemIds = $this->getParam('items');
        $folderIds = $this->getParam('folders');
        $bitsreamid = $this->getParam('bitstream');
        $sessionUser = $this->userSession->Dao;
        $testingMode = Zend_Registry::get('configGlobal')->environment == 'testing';
        if ($sessionUser != null) {
            // Make sure this is a copy and not a reference
            $sessionUser = $this->User->load($sessionUser->getKey());
        } else { // see if module can authenticate with a special parameter
            $authToken = $this->getParam('authToken');
            if (isset($authToken)) {
                $responses = Zend_Registry::get('notifier')->callback(
                    'CALLBACK_CORE_PARAMETER_AUTHENTICATION',
                    array('authToken' => $authToken)
                );
                foreach ($responses as $user) {
                    $sessionUser = $user;
                    break;
                }
            }
        }
        $offset = $this->getParam('offset');
        if (!isset($offset)) {
            $offset = 0;
        }

        if (isset($bitsreamid) && is_numeric($bitsreamid)) {
            $name = $this->getParam('name');
            $bitstream = $this->Bitstream->load($bitsreamid);
            if (!$bitstream) {
                throw new Zend_Exception('Invalid bitstream id', 404);
            }
            if (isset($name)) {
                $bitstream->setName($name); // don't save name, just set it on this dao
            }
            $revision = $bitstream->getItemrevision();
            $item = $revision->getItem();
            if ($item == false || !$this->Item->policyCheck($item, $sessionUser)
            ) {
                throw new Zend_Exception('Permission denied');
            }
            $this->Component->DownloadBitstream->download($bitstream, $offset, true);

            return;
        }
        if (!isset($itemIds) && !isset($folderIds)) {
            throw new Zend_Exception("No parameters, expecting itemIds or folderIds.");
        }
        $folderIds = explode('-', $folderIds);
        $folders = $this->Folder->load($folderIds);

        $item = null;
        $itemIds = explode('-', $itemIds);
        $revisions = array();
        if (!empty($itemIds)) {
            foreach ($itemIds as $itemId) {
                // check revision
                $tmp = explode(',', $itemId);
                if (empty($tmp[0])) {
                    continue;
                }
                $item = $this->Item->load($tmp[0]);
                if (!$item) {
                    throw new Zend_Exception('Item does not exist: '.$tmp[0], 404);
                }
                if (!$this->Item->policyCheck($item, $sessionUser)) {
                    throw new Zend_Exception('Read permission required on item '.$tmp[0], 403);
                }
                if (isset($tmp[1])) {
                    $tmp = $this->Item->getRevision($item, $tmp[1]);
                    if ($tmp !== false) {
                        $revisions[] = $tmp;
                    }
                } else {
                    $tmp = $this->Item->getLastRevision($item);
                    if ($tmp !== false) {
                        $revisions[] = $tmp;
                    }
                }
            }
        }

        if (empty($folders) && empty($revisions)) {
            // download an empty zip with the name of item (if it exists), then exit
            $this->_downloadEmptyItem($item);
        } elseif (empty($folders) && count($revisions) == 1) {
            $revision = $revisions[0];
            $bitstreams = $revision->getBitstreams();

            if ($testingMode) {
                $bitstreams = array($bitstreams[0]);
            }

            if (count($bitstreams) == 0) {
                // download an empty zip with the name of item (if it exists), then exit
                $this->Item->incrementDownloadCount($revision->getItem());
                $this->_downloadEmptyItem($revision->getItem());
            } elseif (count($bitstreams) == 1) {
                if (preg_match('/^https?:\/\//', $bitstreams[0]->getPath())) {
                    $this->redirect($bitstreams[0]->getPath());

                    return;
                }
                $this->disableView();
                $this->Component->DownloadBitstream->testingmode = $testingMode;
                $this->Component->DownloadBitstream->download($bitstreams[0], $offset, true);
            } else {
                ob_start();
                $this->_helper->viewRenderer->setNoRender();
                $name = $revision->getItem()->getName();
                $name = substr($name, 0, 50);
                session_write_close(); // unlock session writing for concurrent access
                $zip = new ZipStream($name.'.zip');
                foreach ($bitstreams as $bitstream) {
                    $filename = $bitstream->getName();
                    $path = $bitstream->getAssetstore()->getPath().'/'.$bitstream->getPath();
                    Zend_Registry::get('dbAdapter')->closeConnection();
                    $zip->add_file_from_path($filename, $path);
                }
                $zip->finish();
                $this->Item->incrementDownloadCount($revision->getItem());
                exit();
            }
        } else {
            $this->_helper->viewRenderer->setNoRender();
            if (count($folders) == 1 && empty($revisions)) {
                $name = $folders[0]->getName();
                $name = substr($name, 0, 50);

                $rootFolder = $this->Folder->getRoot($folders[0]);
                if ($rootFolder) {
                    // Find the Community or the User which have the folder
                    $rootCom = $this->Community->getByFolder($rootFolder);
                    if (!$rootCom) {
                        $user = $this->User->getByFolder($rootFolder);
                        $name = $user->getFirstname().$user->getLastname().'-'.$name;
                    } else {
                        $name = $rootCom->getName().'-'.$name;
                    }
                }
            } else {
                $name = "Custom";
            }

            session_write_close(); // unlock session writing for concurrent access
            ob_start();
            $zip = new ZipStream($name.'.zip');
            UtilityComponent::disableMemoryLimit();
            foreach ($revisions as $revision) {
                $item = $revision->getItem();
                $bitstreams = $revision->getBitstreams();
                $count = count($bitstreams);

                foreach ($bitstreams as $bitstream) {
                    if ($count > 1 || $bitstream->getName() != $item->getName()
                    ) {
                        $path = $item->getName().'/';
                    } else {
                        $path = '';
                    }
                    $filename = $path.$bitstream->getName();
                    $fullpath = $bitstream->getAssetstore()->getPath().'/'.$bitstream->getPath();
                    Zend_Registry::get('dbAdapter')->closeConnection();
                    $zip->add_file_from_path($filename, $fullpath);
                }
                $this->Item->incrementDownloadCount($item);
                unset($item);
                unset($bitstreams);
            }
            foreach ($folders as $folder) {
                if (!$this->Folder->policyCheck($folder, $sessionUser)) {
                    continue;
                }
                $this->Folder->zipStream($zip, $folder->getName(), $folder, $sessionUser);
            }
            $zip->finish();
            exit();
        }
    }

    /**
     * Ajax action for determining what action to take based on the size of the requested download.
     *
     * @throws Zend_Exception
     */
    public function checksizeAction()
    {
        $this->disableView();
        $this->disableLayout();
        $itemIds = $this->getParam('itemIds');
        $folderIds = $this->getParam('folderIds');
        if (isset($itemIds)) {
            $itemIdArray = explode(',', $itemIds);
        } else {
            $itemIdArray = array();
        }

        if (isset($folderIds)) {
            $folderIdArray = explode(',', $folderIds);
        } else {
            $folderIdArray = array();
        }

        $items = array();
        $folders = array();
        foreach ($itemIdArray as $itemId) {
            if ($itemId) {
                $item = $this->Item->load($itemId);
                if (!$item || !$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ)
                ) {
                    throw new Zend_Exception('Permission denied on item '.$itemId, 403);
                }
                $items[] = $item;
            }
        }
        foreach ($folderIdArray as $folderId) {
            if ($folderId) {
                $folder = $this->Folder->load($folderId);
                if (!$folder || !$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_READ)
                ) {
                    throw new Zend_Exception('Permission denied on folder '.$folderId, 403);
                }
                $folders[] = $folder;
            }
        }
        $totalSize = 0;
        $folders = $this->Folder->getSizeFiltered($folders, $this->userSession->Dao, MIDAS_POLICY_READ);
        foreach ($folders as $folder) {
            $totalSize += $folder->size;
        }
        foreach ($items as $item) {
            $totalSize += $item->getSizebytes();
        }

        if ($totalSize > 2E9 && in_array('javauploaddownload', Zend_Registry::get('modulesEnable'))
        ) {
            echo JsonComponent::encode(
                array('action' => 'promptApplet', 'sizeStr' => UtilityComponent::formatSize($totalSize))
            );
        } else {
            echo JsonComponent::encode(array('action' => 'download'));
        }
    }

    /**
     * This action exposes downloading a single item and should be called as
     *   download/item/<item_id>/...
     * Any extra parameters are ignored and can be used to force clients like wget to download to the correct filename
     * if the content-disposition header is ignored by the user agent.
     * @throws Zend_Exception
     */
    public function itemAction()
    {
        $pathParams = UtilityComponent::extractPathParams();
        if (empty($pathParams)) {
            throw new Zend_Exception('Must specify item id as a path parameter');
        }

        $this->forward('index', null, null, array('items' => $pathParams[0]));
    }

    /**
     * This action exposes downloading a single folder and should be called as
     *   download/folder/<folder_id>/...
     * Any extra parameters are ignored and can be used to force clients like wget to download to the correct filename
     * if the content-disposition header is ignored by the user agent.
     *
     * @throws Zend_Exception
     */
    public function folderAction()
    {
        $pathParams = UtilityComponent::extractPathParams();
        if (empty($pathParams)) {
            throw new Zend_Exception('Must specify folder id as a path parameter');
        }

        $this->forward('index', null, null, array('folders' => $pathParams[0]));
    }

    /**
     * This action exposes downloading a single bitstream and should be called as
     *   download/bitstream/<bitstream_id>/...
     * Any extra parameters are ignored and can be used to force clients like wget to download to the correct filename
     * if the content-disposition header is ignored by the user agent.
     *
     * @throws Zend_Exception
     */
    public function bitstreamAction()
    {
        $pathParams = UtilityComponent::extractPathParams();
        if (empty($pathParams)) {
            throw new Zend_Exception('Must specify bitstream id as a path parameter');
        }

        $this->forward('index', null, null, array('bitstream' => $pathParams[0]));
    }

    /**
     * will download a zip file with the same name as the item name,
     * if the item exists, then will exit.
     *
     * @param type $item
     */
    private function _downloadEmptyItem($item)
    {
        ob_start();
        $this->disableView();
        if (isset($item) && $item instanceof ItemDao) {
            $name = $item->getName();
        } else {
            $name = "No_item_selected";
        }
        $name = substr($name, 0, 50);
        if (headers_sent()) {
            echo $name; // this is used in testing mode since we cannot send headers from ZipStream

            return;
        }
        $zip = new ZipStream($name.'.zip');
        $zip->finish();
        exit();
    }
}
