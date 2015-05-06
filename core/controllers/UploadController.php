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

/** Upload Controller */
class UploadController extends AppController
{
    public $_components = array('Upload');
    public $_forms = array('Upload');
    public $_models = array('Assetstore', 'Folder', 'Folderpolicygroup', 'Folderpolicyuser', 'Item', 'License', 'Setting');

    /** Init controller */
    public function init()
    {
        $maxFile = str_replace('M', '', ini_get('upload_max_filesize'));
        $maxPost = str_replace('M', '', ini_get('post_max_size'));
        if ($maxFile < $maxPost) {
            $this->view->maxSizeFile = $maxFile * 1024 * 1024;
        } else {
            $this->view->maxSizeFile = $maxPost * 1024 * 1024;
        }

        if ($this->isTestingEnv()) {
            $assetstores = $this->Assetstore->getAll();
            if (empty($assetstores)) {
                $assetstoreDao = new AssetstoreDao();
                $assetstoreDao->setName('Default');
                $assetstoreDao->setPath($this->getDataDirectory('assetstore'));
                $assetstoreDao->setType(MIDAS_ASSETSTORE_LOCAL);
                $this->Assetstore = new AssetstoreModel(); // reset Database adapter
                $this->Assetstore->save($assetstoreDao);
            } else {
                $assetstoreDao = $assetstores[0];
            }
            $this->Setting->setConfig('default_assetstore', $assetstoreDao->getKey());
        }
    }

    /** Simple upload */
    public function simpleuploadAction()
    {
        if (!$this->logged) {
            throw new Zend_Exception('You have to be logged in to do that');
        }
        if (!$this->isTestingEnv()) {
            $this->requireAjaxRequest();
        }
        $this->disableLayout();
        $this->view->form = $this->getFormAsArray($this->Form->Upload->createUploadLinkForm());
        $this->userSession->filePosition = null;
        $this->view->selectedLicense = Zend_Registry::get('configGlobal')->defaultlicense;
        $this->view->allLicenses = $this->License->getAll();

        $this->view->defaultUploadLocation = '';
        $this->view->defaultUploadLocationText = 'You must select a folder';

        $parent = $this->getParam('parent');
        if (isset($parent)) {
            $parent = $this->Folder->load($parent);
            if ($this->logged && $parent != false) {
                $this->view->defaultUploadLocation = $parent->getKey();
                $this->view->defaultUploadLocationText = $parent->getName();
            }
        } else {
            $parent = null;
        }
        $this->view->extraHtml = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_GET_SIMPLEUPLOAD_EXTRA_HTML',
            array('folder' => $parent)
        );
        $this->view->customTabs = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_UPLOAD_TABS', array());
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
        if ($itemRevision === false) {
            $this->view->selectedLicense = Zend_Registry::get('configGlobal')->defaultlicense;
        } else {
            $this->view->selectedLicense = $itemRevision->getLicenseId();
        }

        $this->view->allLicenses = $this->License->getAll();

        if (array_key_exists('HTTPS', $_SERVER) && $_SERVER["HTTPS"] === 'on') {
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
            'CALLBACK_CORE_GET_REVISIONUPLOAD_EXTRA_HTML',
            array('item' => $item)
        );
        $this->view->customTabs = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_GET_REVISIONUPLOAD_TABS',
            array()
        );
    }

    /** Save a link */
    public function savelinkAction()
    {
        if (!$this->logged) {
            throw new Zend_Exception('You have to be logged in to do that');
        }
        if (!$this->isTestingEnv()) {
            $this->requireAjaxRequest();
        }

        $this->disableLayout();
        $this->disableView();
        $name = $this->getParam('name');
        $url = $this->getParam('url');
        $parent = $this->getParam('parent');
        if (!empty($url) && !empty($name)) {
            $this->Component->Upload->createLinkItem($this->userSession->Dao, $name, $url, $parent);
        }
    }
}
