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

/** Upload controller for the javauploaddownload module */
class Javauploaddownload_UploadController extends Javauploaddownload_AppController
{
    public $_components = array('Httpupload', 'Upload');
    public $_models = array('Assetstore', 'Folder', 'Item', 'License', 'Setting');

    private function _is_https()
    {
        return array_key_exists('HTTPS', $_SERVER) && $_SERVER["HTTPS"] === 'on';
    }

    /** Init controller */
    public function init()
    {
        if ($this->isTestingEnv()) {
            $assetstores = $this->Assetstore->getAll();

            if (empty($assetstores)) {
                $assetstoreDao = new AssetstoreDao();
                $assetstoreDao->setName('Default');
                $assetstoreDao->setPath($this->getDataDirectory('assetstore'));
                $assetstoreDao->setType(MIDAS_ASSETSTORE_LOCAL);
                $this->Assetstore = new AssetstoreModel();
                $this->Assetstore->save($assetstoreDao);
            } else {
                $assetstoreDao = $assetstores[0];
            }
            $this->Setting->setConfig('default_assetstore', $assetstoreDao->getKey());
        }
    }

    /** Render the large file upload view */
    public function indexAction()
    {
        if (!$this->logged) {
            throw new Zend_Exception('You have to be logged in to do that');
        }

        $this->requireAjaxRequest();
        $this->disableLayout();
        $mode = $this->getParam('mode');
        $this->view->directoryMode = isset($mode) && $mode == 'folder';
        session_start();

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

        $this->view->selectedLicense = Zend_Registry::get('configGlobal')->defaultlicense;
        $this->view->allLicenses = $this->License->getAll();
        $this->view->defaultUploadLocation = '';
        $this->view->defaultUploadLocationText = $this->t('You must select a folder');
        $parent = $this->getParam('parent');
        $license = $this->getParam('license');

        if (!empty($parent) && !empty($license)) {
            $this->disableView();
            $this->userSession->JavaUpload->parent = $parent;
            $this->userSession->JavaUpload->license = $license;
        } else {
            $this->userSession->JavaUpload->parent = null;
        }

        if (isset($parent)) {
            $folder = $this->Folder->load($parent);

            if ($this->logged && $folder != false) {
                $this->view->defaultUploadLocation = $folder->getKey();
                $this->view->defaultUploadLocationText = $folder->getName();
            }
        } else {
            $folder = null;
        }

        $this->view->extraHtml = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_GET_JAVAUPLOAD_EXTRA_HTML',
            array('folder' => $folder)
        );
        session_write_close();
    }

    /** Upload new revision */
    public function revisionAction()
    {
        if (!$this->logged) {
            throw new Zend_Exception('You have to be logged in to do that');
        }

        if (!$this->isTestingEnv()) {
            $this->requireAjaxRequest();
        }

        $this->disableLayout();
        $itemId = $this->getParam('itemId');
        $item = $this->Item->load($itemId);

        if ($item == false) {
            throw new Zend_Exception('Unable to load item.');
        }

        if (!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_WRITE)
        ) {
            throw new Zend_Exception('Error policies.');
        }

        $this->view->item = $item;
        $itemRevision = $this->Item->getLastRevision($item);
        $this->view->lastrevision = $itemRevision;

        // Check if the revision exists and if it does, we send its license ID to
        // the view. If it does not exist we use our default license
        if ($itemRevision) {
            $this->view->selectedLicense = $itemRevision->getLicenseId();
        } else {
            $this->view->selectedLicense = Zend_Registry::get('configGlobal')->defaultlicense;
        }

        $this->view->allLicenses = $this->License->getAll();

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

        $this->view->extraHtml = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_GET_JAVAREVISIONUPLOAD_EXTRA_HTML',
            array('item' => $item)
        );
    }

    /**
     * Handles the user setting changes and licenses when using the large revision upload applet
     */
    public function javarevisionsessionAction()
    {
        if (!$this->logged) {
            throw new Zend_Exception('You have to be logged in to do that');
        }

        $this->disableLayout();
        $this->disableView();
        $changes = $this->getParam('changes');
        $license = $this->getParam('license');
        $itemId = $this->getParam('itemId');
        $item = $this->Item->load($itemId);

        if (!isset($itemId) || !$item) {
            throw new Zend_Exception('Invalid itemId', 404);
        }
        $rev = $this->Item->getLastRevision($item);

        if ($rev) {
            $revNumber = $rev->getRevision() + 1;
        } else {
            $revNumber = 1;
        }

        session_start();
        $this->userSession->JavaUpload->changes = $changes;
        $this->userSession->JavaUpload->license = $license;
        $this->userSession->JavaUpload->revNumber = $revNumber;
        session_write_close();
    }

    /**
     * Used when uploading a folder with the applet. Prints the destination folder id
     */
    public function javadestinationfolderAction()
    {
        $this->disableView();
        $this->disableLayout();

        if (Zend_Registry::get('configGlobal')->environment != 'testing') {
            // give a week-long session cookie in case the download lasts a long time
            session_start();
            $this->userSession->setExpirationSeconds(60 * 60 * 24 * 7);
            session_write_close();
        }

        echo $this->userSession->JavaUpload->parent;
    }

    /**
     * Used to see how much of a file made it to the server during an interrupted upload attempt
     *
     * @param uploadUniqueIdentifier The upload token to check
     */
    public function gethttpuploadoffsetAction()
    {
        $this->disableLayout();
        $this->disableView();
        $params = $this->getAllParams();
        $userId = explode('/', $params['uploadUniqueIdentifier'])[0];

        if ($userId != $this->userSession->Dao->getUserId()) {
            echo '[ERROR]User id does not match upload token user id';
            throw new Zend_Exception('User id does not match upload token user id');
        }

        $this->Component->Httpupload->setTokenParamName('uploadUniqueIdentifier');
        $offset = $this->Component->Httpupload->getOffset($params);
        echo '[OK]'.$offset['offset'];
    }

    /**
     * Get a unique upload token for the java uploader. Must be logged in to do this
     *
     * @param filename The name of the file to be uploaded
     */
    public function gethttpuploaduniqueidentifierAction()
    {
        $this->disableLayout();
        $this->disableView();
        $params = $this->getAllParams();

        if (!$this->logged) {
            throw new Zend_Exception('You have to be logged in to do that');
        }

        if (isset($params['parentFolderId'])) {
            $parentId = $params['parentFolderId'];
            $folder = $this->Folder->load($parentId);

            if (!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_WRITE)
            ) {
                throw new Zend_Exception('Write permissions required');
            }
        } elseif (isset($params['revision'])) {
            $parentId = $params['itemId'];
            $item = $this->Item->load($parentId);

            if (!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_WRITE)
            ) {
                throw new Zend_Exception('Write permissions required');
            }
        } elseif (!isset($params['testingmode']) && $this->userSession->JavaUpload->parent) {
            $parentId = $this->userSession->JavaUpload->parent;
            $folder = $this->Folder->load($parentId);

            if (!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_WRITE)
            ) {
                throw new Zend_Exception('Write permissions required');
            }
        } else {
            echo '[ERROR]You must specify a parent folder or item.';

            return;
        }

        $dir = $this->userSession->Dao->getUserId().'/'.$parentId;

        try {
            $token = $this->Component->Httpupload->generateToken($params, $dir);
            echo '[OK]'.$token['token'];
        } catch (Exception $e) {
            echo '[ERROR]'.$e->getMessage();
            throw $e;
        }
    }

    /**
     * Process a java upload
     *
     * @param uploadUniqueIdentifier The upload token (see gethttpuploaduniqueidentifierAction)
     * @param filename The name of the file being uploaded
     * @param length The length of the file being uploaded
     * @param [parentId] Optionally specify the parent id explicitly (used for folder upload)
     */
    public function processjavauploadAction()
    {
        $this->disableLayout();
        $this->disableView();
        $params = $this->getAllParams();

        if (!$this->logged) {
            echo '[ERROR]You must be logged in to upload';
            throw new Zend_Exception('You have to be logged in to do that');
        }

        list($userId, $parentId) = explode('/', $params['uploadUniqueIdentifier']);

        if ($userId != $this->userSession->Dao->getUserId()) {
            echo '[ERROR]User id does not match upload token user id';
            throw new Zend_Exception('User id does not match upload token user id');
        }

        if (isset($params['parentId'])) {
            $expectedParentId = $params['parentId'];
        } elseif (!isset($params['testingmode']) && $this->userSession->JavaUpload->parent) {
            $expectedParentId = $this->userSession->JavaUpload->parent;
        } else {
            throw new Zend_Exception('You must set a parentId parameter or java session variable');
        }

        if ($parentId != $expectedParentId) {
            echo '[ERROR]You are attempting to upload into the incorrect parent folder';
            throw new Zend_Exception('You are attempting to upload into the incorrect parent folder');
        }

        $testingMode = Zend_Registry::get('configGlobal')->environment == 'testing';
        $this->Component->Httpupload->setTestingMode($testingMode);
        $this->Component->Httpupload->setTokenParamName('uploadUniqueIdentifier');
        $data = $this->Component->Httpupload->process($params);
        $validations = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_VALIDATE_UPLOAD',
            array(
                'filename' => $data['filename'],
                'size' => $data['size'],
                'path' => $data['path'],
                'folderId' => $parentId,
            )
        );
        foreach ($validations as $validation) {
            if (!$validation['status']) {
                unlink($data['path']);
                echo '[ERROR]'.$validation['message'];
                throw new Zend_Exception($validation['message']);
            }
        }

        if (!empty($data['path']) && file_exists($data['path']) && $data['size'] == $params['length']
        ) {
            if (isset($params['parentId'])) {
                $parent = $params['parentId'];
            } elseif (!isset($params['testingmode']) && isset($this->userSession->JavaUpload->parent)) {
                $parent = $this->userSession->JavaUpload->parent;
            } else {
                $parent = null;
            }

            if (isset($this->userSession->JavaUpload->license)) {
                $license = $this->userSession->JavaUpload->license;
            } else {
                $license = null;
            }

            try {
                $newRevision = (bool) $this->getParam(
                    'newRevision'
                ); // on name collision, should we create new revision?
                $item = $this->Component->Upload->createUploadedItem(
                    $this->userSession->Dao,
                    $data['filename'],
                    $data['path'],
                    $parent,
                    $license,
                    $data['md5'],
                    (bool) $testingMode,
                    $newRevision
                );
            } catch (Exception $e) {
                if (!$testingMode) {
                    unlink($data['path']);
                }

                echo '[ERROR] '.$e->getMessage();
                throw $e;
            }

            echo '[OK]'.$item->getKey();
        } else {
            echo '[ERROR] Data path ('.$data['path'].') was empty or file not created. Sizes='.$params['length'].'/'.$data['size'];
        }
    }

    /**
     * Process a java upload of a new revision
     *
     * @param uploadUniqueIdentifier The upload token (see gethttpuploaduniqueidentifierAction)
     * @param filename The name of the file being uploaded
     * @param length The length of the file being uploaded
     * @param item The id of the item being uploaded into
     */
    public function processjavarevisionuploadAction()
    {
        $this->disableLayout();
        $this->disableView();
        $params = $this->getAllParams();

        if (!$this->logged) {
            echo '[ERROR]You must be logged in to upload';
            throw new Zend_Exception('You have to be logged in to do that');
        }

        list($userId, $parentId) = explode('/', $params['uploadUniqueIdentifier']);

        if ($userId != $this->userSession->Dao->getUserId()) {
            echo '[ERROR]User id does not match upload token user id';
            throw new Zend_Exception('User id does not match upload token user id');
        }

        if ($params['itemId'] != $parentId) {
            echo '[ERROR]You are attempting to upload into the incorrect parent item';
            throw new Zend_Exception('You are attempting to upload into the incorrect parent item');
        }

        $testingMode = Zend_Registry::get('configGlobal')->environment == 'testing';
        $this->Component->Httpupload->setTestingMode($testingMode);
        $this->Component->Httpupload->setTokenParamName('uploadUniqueIdentifier');
        $data = $this->Component->Httpupload->process($params);
        $item = $this->Item->load($parentId);
        $parentFolders = $item->getFolders();
        $validations = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_VALIDATE_UPLOAD',
            array(
                'filename' => $data['filename'],
                'size' => $data['size'],
                'path' => $data['path'],
                'folderId' => $parentFolders[0]->getKey(),
            )
        );

        foreach ($validations as $validation) {
            if (!$validation['status']) {
                unlink($data['path']);
                echo '[ERROR]'.$validation['message'];
                throw new Zend_Exception($validation['message']);
            }
        }

        if (!empty($data['path']) && file_exists($data['path']) && $data['size'] > 0
        ) {
            if (!isset($params['testingmode']) && isset($this->userSession->JavaUpload->changes)) {
                $changes = $this->userSession->JavaUpload->changes;
            } else {
                $changes = '';
            }

            if (isset($this->userSession->JavaUpload->license)) {
                $license = $this->userSession->JavaUpload->license;
            } else {
                $license = null;
            }

            if (isset($this->userSession->JavaUpload->revNumber)) {
                $revNumber = $this->userSession->JavaUpload->revNumber;
            } else {
                $revNumber = 1;
            }

            try {
                $this->Component->Upload->createNewRevision(
                    $this->userSession->Dao,
                    $data['filename'],
                    $data['path'],
                    $changes,
                    $parentId,
                    $revNumber,
                    $license,
                    $data['md5'],
                    (bool) $testingMode
                );
            } catch (Exception $e) {
                if (!$testingMode) {
                    unlink($data['path']);
                }

                echo "[ERROR] ".$e->getMessage();
                throw $e;
            }

            echo "[OK]";
        }
    }

    /** Link for the java applet to review uploaded files */
    public function reviewAction()
    {
        if ($this->userSession->JavaUpload->parent) {
            $this->redirect('/folder/'.$this->userSession->JavaUpload->parent);
        } else {
            $this->redirect('/community/');
        }
    }
}
