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

/** Controller for downloading key files */
class Keyfiles_DownloadController extends Keyfiles_AppController
{
    var $_models = array('Bitstream', 'Folder', 'Item');

    /**
     * Download all key files for the head revision of an item
     */
    public function itemAction()
    {
        $itemId = $this->getParam('itemId');
        if (!isset($itemId)) {
            throw new Exception('Must pass an itemId parameter');
        }
        $item = $this->Item->load($itemId);
        if (!$item) {
            throw new Zend_Exception('Invalid itemId', 404);
        }
        if (!$this->Item->policyCheck($item, $this->userSession->Dao)) {
            throw new Zend_Exception('Read permission required', 403);
        }
        $revision = $this->Item->getLastRevision($item);
        if (!$revision) {
            throw new Zend_Exception('Item must have at least one revision', 404);
        }
        $this->disableView();
        $this->disableLayout();

        if (headers_sent()) {
            return;
        }

        $this->_emptyOutputBuffer();
        ob_start(); //must start a new buffer for ZipStream to work
        $zip = new \ZipStream\ZipStream($item->getName().'.zip');
        $bitstreams = $revision->getBitstreams();
        foreach ($bitstreams as $bitstream) {
            $zip->addFile($bitstream->getName().'.md5', $bitstream->getChecksum());
        }
        $zip->finish();
        exit();
    }

    /**
     * Download the key file for a specific bitstream
     */
    public function bitstreamAction()
    {
        $bitstreamId = $this->getParam('bitstreamId');
        if (!isset($bitstreamId)) {
            throw new Exception('Must pass a bitstreamId parameter');
        }
        $bitstream = $this->Bitstream->load($bitstreamId);
        if (!$bitstream) {
            throw new Zend_Exception('Invalid bitstreamId', 404);
        }
        $item = $bitstream->getItemrevision()->getItem();
        if (!$this->Item->policyCheck($item, $this->userSession->Dao)) {
            throw new Zend_Exception('Read permission required', 403);
        }
        $this->disableView();
        $this->disableLayout();

        $checksum = $bitstream->getChecksum();

        $download = !headers_sent();
        if ($download) {
            $this->_emptyOutputBuffer();
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.$bitstream->getName().'.md5"');
            header('Content-Length: '.strlen($checksum));
            header('Expires: 0');
            header('Accept-Ranges: bytes');
            header('Cache-Control: private', false);
            header('Pragma: private');
        }

        echo $checksum;

        if ($download) {
            exit();
        }
    }

    /**
     * Kill the whole ob stack (Zend uses double nested output buffers)
     */
    private function _emptyOutputBuffer()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Download key files for a selected group of folders and/or items
     * @param items List of item id's separated by -
     * @param folders List of folder id's separated by -
     */
    public function batchAction()
    {
        UtilityComponent::disableMemoryLimit();
        $itemIds = $this->getParam('items');
        $folderIds = $this->getParam('folders');
        if (!isset($itemIds) && !isset($folderIds)) {
            throw new Zend_Exception('No parameters');
        }
        $this->disableLayout();
        $this->disableView();
        $folderIds = explode('-', $folderIds);
        $folders = $this->Folder->load($folderIds);

        $itemIds = explode('-', $itemIds);
        $items = $this->Item->load($itemIds);

        if (headers_sent()) {
            return; // can't do anything if headers sent already
        }
        $this->_emptyOutputBuffer();
        ob_start(); //must start a new output buffer for ZipStream to work
        $zip = new \ZipStream\ZipStream('Keyfiles.zip');
        // Iterate over top level items
        foreach ($items as $item) {
            if (!$this->Item->policyCheck($item, $this->userSession->Dao)) {
                $this->getLogger()->warn('Keyfiles: Permission failure, skipping item '.$item->getKey());
                continue;
            }
            $revision = $this->Item->getLastRevision($item);
            if (!$revision) {
                continue;
            }
            $bitstreams = $revision->getBitstreams();
            $count = count($bitstreams);

            foreach ($bitstreams as $bitstream) {
                if ($count > 1 || $bitstream->getName() != $item->getName()) {
                    $path = $item->getName().'/';
                } else {
                    $path = '';
                }
                $filename = $path.$bitstream->getName().'.md5';
                Zend_Registry::get('dbAdapter')->closeConnection();
                $zip->addFile($filename, $bitstream->getChecksum());
            }
            $this->Item->incrementDownloadCount($item);
            unset($item);
            unset($revision);
            unset($bitstreams);
        }

        // Iterate over top level folders, stream them out recursively using FolderModel::zipStream()
        foreach ($folders as $folder) {
            if (!$this->Folder->policyCheck($folder, $this->userSession->Dao)) {
                $this->getLogger()->warn('Keyfiles: Permission failure, skipping folder '.$folder->getKey());
                continue;
            }
            $callable = array($this, 'outputCallback');
            $this->Folder->zipStream($zip, $folder->getName(), $folder, $this->userSession->Dao, $callable);
        }
        $zip->finish();
        exit();
    }

    /**
     * Output callback override function for use in FolderModel::zipStream.
     * Overrides the default behavior of streaming the file, and instead just
     * adds a file whose contents are the bitstream's checksum
     */
    public function outputCallback(&$zip, $path, $item, $bitstream, $count)
    {
        if ($count > 1 || $bitstream->getName() != $item->getName()) {
            $path .= '/'.$item->getName();
        }
        $path .= '/'.$bitstream->getName().'.md5';
        $zip->addFile($path, $bitstream->getChecksum());
    }
}
