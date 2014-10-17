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

/** Download controller for the javauploaddownload module */
class Javauploaddownload_DownloadController extends Javauploaddownload_AppController
{
    public $_models = array('Folder', 'Item');

    private function _is_https()
    {
        return array_key_exists('HTTPS', $_SERVER) && $_SERVER["HTTPS"] === 'on';
    }

    /**
     * Render the view for the Java download applet
     *
     * @param itemIds Comma separated list of items to download
     * @param folderIds Comma separated list of folders to download
     */
    public function indexAction()
    {
        $folderIds = $this->getParam('folderIds');

        if (isset($folderIds) && $folderIds) {
            $folderIdArray = explode(',', $folderIds);
            $this->view->folderIds = $folderIds;
        } else {
            $folderIdArray = array();
            $this->view->folderIds = '';
        }

        $itemIds = $this->getParam('itemIds');

        if (isset($itemIds) && $itemIds) {
            $itemIdArray = explode(',', $itemIds);
            $this->view->itemIds = $itemIds;
        } else {
            $itemIdArray = array();
            $this->view->itemIds = '';
        }

        $items = array();
        $folders = array();

        foreach ($itemIdArray as $itemId) {
            if ($itemId) {
                $item = $this->Item->load($itemId);

                if (!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ)
                ) {
                    throw new Zend_Exception('Invalid policy on item '.$itemId, 403);
                }
                $items[] = $item;
            }
        }

        foreach ($folderIdArray as $folderId) {
            if ($folderId) {
                $folder = $this->Folder->load($folderId);

                if (!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_READ)
                ) {
                    throw new Zend_Exception('Invalid policy on folder '.$folderId, 403);
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

        $this->view->totalSize = $totalSize;
        $fCount = count($folderIdArray);
        $iCount = count($itemIdArray);

        if ($iCount === 0 && $fCount === 0) {
            throw new Zend_Exception('No items or folders specified');
        } elseif ($iCount === 1 && $fCount === 0) {
            $item = $this->Item->load($itemIdArray[0]);
            $this->view->contentDescription = 'Item <b>'.$item->getName().'</b>';
        } elseif ($iCount === 0 && $fCount === 1) {
            $folder = $this->Folder->load($folderIdArray[0]);
            $this->view->contentDescription = 'Folder <b>'.$folder->getName().'</b>';
        } elseif ($iCount === 0) {
            $this->view->contentDescription = $fCount.' folders';
        } elseif ($fCount === 0) {
            $this->view->contentDescription = $iCount.' items';
        } else {
            $this->view->contentDescription = $fCount.' folder(s) and '.$iCount.' item(s)';
        }

        if ($this->_is_https()) {
            $this->view->protocol = 'https';
        } else {
            $this->view->protocol = 'http';
        }

        if (!$this->isTestingEnv()) {
            $this->view->host = empty($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['HTTP_X_FORWARDED_HOST'];
        } else {
            $this->view->host = 'localhost';
        }

        $this->view->header = 'Java Download Applet';
        $this->view->extraHtml = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_GET_JAVADOWNLOAD_EXTRA_HTML',
            array('folders' => $folders, 'items' => $items)
        );
    }
}
