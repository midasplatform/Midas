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

/** Item Controller */
class ItemController extends AppController
{
    public $_models = array('Item', 'ItemRevision', 'Bitstream', 'Folder', 'Metadata', 'License', 'Progress');
    public $_daos = array();
    public $_components = array('Breadcrumb', 'Date', 'Sortdao');
    public $_forms = array('Item');

    /**
     * Init Controller
     */
    public function init()
    {
        $this->view->activemenu = ''; // set the active menu
        $actionName = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
        if (isset($actionName) && is_numeric($actionName)) {
            $this->forward('view', null, null, array('itemId' => $actionName));
        }
    }

    /**
     * create/edit metadata
     *
     * @throws Zend_Exception on non-logged user, invalid itemId and incorrect access permission
     */
    public function editmetadataAction()
    {
        $this->disableLayout();
        if (!$this->logged) {
            throw new Zend_Exception(MIDAS_LOGIN_REQUIRED);
        }

        $itemId = $this->getParam('itemId');
        $metadataId = $this->getParam('metadataId');
        $itemDao = $this->Item->load($itemId);
        if ($itemDao === false) {
            throw new Zend_Exception("This item doesn't exist.", 404);
        }
        if (!$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_WRITE)
        ) {
            throw new Zend_Exception("Write permissions required", 403);
        }

        $itemRevisionNumber = $this->getParam("itemrevision");
        if (isset($itemRevisionNumber)) {
            $this->view->itemrevision = $itemRevisionNumber;
            $metadataItemRevision = $this->Item->getRevision($itemDao, $itemRevisionNumber);
        } else {
            $metadataItemRevision = $this->Item->getLastRevision($itemDao);
        }
        $metadatavalues = $this->ItemRevision->getMetadata($metadataItemRevision);
        $this->view->metadata = null;

        foreach ($metadatavalues as $value) {
            if ($value->getMetadataId() == $metadataId) {
                $this->view->metadata = $value;
                break;
            }
        }

        $this->view->itemDao = $itemDao;
        $this->view->metadataTypes = array(
            MIDAS_METADATA_TEXT => 'Text',
            MIDAS_METADATA_INT => 'Integer',
            MIDAS_METADATA_LONG => 'Long Integer',
            MIDAS_METADATA_FLOAT => 'Floating Point',
            MIDAS_METADATA_DOUBLE => 'Double Precision',
            MIDAS_METADATA_STRING => 'String',
            MIDAS_METADATA_BOOLEAN => 'Boolean',
        );
    }

    /**
     * View a Item
     *
     * @throws Zend_Exception on invalid itemId and incorrect access permission
     */
    public function viewAction()
    {
        $this->view->Date = $this->Component->Date;
        $itemId = $this->getParam("itemId");
        if (!isset($itemId) || !is_numeric($itemId)) {
            throw new Zend_Exception("itemId should be a number");
        }
        $itemDao = $this->Item->load($itemId);
        if ($itemDao === false) {
            throw new Zend_Exception("This item doesn't exist.", 404);
        }
        if (!$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            throw new Zend_Exception('Read permission required', 403);
        }

        $this->view->isAdmin = $this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
        $this->view->isModerator = $this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_WRITE);
        $itemRevision = $this->Item->getLastRevision($itemDao);
        if ($this->_request->isPost()) {
            $itemRevisionNumber = $this->getParam("itemrevision");
            if (isset($itemRevisionNumber)) {
                $metadataItemRevision = $this->Item->getRevision($itemDao, $itemRevisionNumber);
            } else {
                $metadataItemRevision = $itemRevision;
            }
            $deleteMetadata = $this->getParam('deleteMetadata');
            $editMetadata = $this->getParam('editMetadata');
            if (isset($deleteMetadata) && !empty($deleteMetadata) && $this->view->isModerator) { // delete metadata field
                $this->disableView();
                $this->disableLayout();
                $metadataId = $this->getParam('element');
                $this->ItemRevision->deleteMetadata($metadataItemRevision, $metadataId);
                echo JsonComponent::encode(array(true, $this->t('Changes saved')));

                return;
            }
            if (isset($editMetadata) && !empty($editMetadata) && $this->view->isModerator) { // add metadata field
                $metadatatype = $this->getParam('metadatatype');
                $element = $this->getParam('element');
                $qualifier = $this->getParam('qualifier');
                $value = $this->getParam('value');
                $updateMetadata = $this->getParam('updateMetadata');
                $metadataDao = $this->Metadata->getMetadata($metadatatype, $element, $qualifier);
                if ($metadataDao == false) {
                    $metadataDao = $this->Metadata->addMetadata($metadatatype, $element, $qualifier, '');
                }
                $metadataDao->setItemrevisionId($metadataItemRevision->getKey());
                $metadataValueExists = $this->Metadata->getMetadataValueExists($metadataDao);
                if ($updateMetadata || !$metadataValueExists) {
                    // if we are updating or no metadatavalue exists, then save it
                    // otherwise we are attempting to add a new value where one already
                    // exists, and we won't save in this case
                    $this->Metadata->addMetadataValue(
                        $metadataItemRevision,
                        $metadatatype,
                        $element,
                        $qualifier,
                        $value
                    );
                }
            }
        }
        if ($this->logged && !$this->isTestingEnv()) {
            $cookieName = hash('sha1', MIDAS_ITEM_COOKIE_NAME.$this->userSession->Dao->getKey());

            /** @var Zend_Controller_Request_Http $request */
            $request = $this->getRequest();
            $cookieData = $request->getCookie($cookieName);
            $recentItems = array();
            if (isset($cookieData)) {
                $recentItems = unserialize($cookieData);
            }
            $tmp = array_reverse($recentItems);
            $i = 0;
            foreach ($tmp as $key => $t) {
                if ($t == $itemDao->getKey() || !is_numeric($t)) {
                    unset($tmp[$key]);
                    continue;
                }
                $i++;
                if ($i > 4) {
                    unset($tmp[$key]);
                }
            }
            $recentItems = array_reverse($tmp);
            $recentItems[] = $itemDao->getKey();
            $date = new DateTime();
            $interval = new DateInterval('P1M');
            setcookie(
                $cookieName,
                serialize($recentItems),
                $date->add($interval)->getTimestamp(),
                '/',
                $request->getHttpHost(),
                (int) Zend_Registry::get('configGlobal')->get('cookie_secure', 0) === 1,
                true
            );
        }

        $this->Item->incrementViewCount($itemDao);
        $itemDao->lastrevision = $itemRevision;
        $itemDao->revisions = $itemDao->getRevisions();

        // Display the good link if the item is pointing to a website
        $this->view->itemIsLink = false;

        if (isset($itemRevision) && $itemRevision !== false) {
            $bitstreams = $itemRevision->getBitstreams();
            if (count($bitstreams) == 1) {
                $bitstream = $bitstreams[0];
                if (preg_match('/^https?:\/\//', $bitstream->getPath())) {
                    $this->view->itemIsLink = true;
                }
            }
            $itemDao->creation = $this->Component->Date->formatDate(strtotime($itemRevision->getDate()));
        }

        // Add the metadata for each revision
        foreach ($itemDao->getRevisions() as $revision) {
            $revision->metadatavalues = $this->ItemRevision->getMetadata($revision);
        }

        $this->Component->Sortdao->field = 'revision';
        $this->Component->Sortdao->order = 'desc';
        usort($itemDao->revisions, array($this->Component->Sortdao, 'sortByNumber'));

        $this->view->itemDao = $itemDao;

        $this->view->itemSize = UtilityComponent::formatSize($itemDao->getSizebytes());

        $this->view->title .= ' - '.$itemDao->getName();
        $this->view->metaDescription = substr($itemDao->getDescription(), 0, 160);

        $tmp = Zend_Registry::get('notifier')->callback('CALLBACK_VISUALIZE_CAN_VISUALIZE', array('item' => $itemDao));
        if (isset($tmp['visualize']) && $tmp['visualize'] == true) {
            $this->view->preview = true;
        } else {
            $this->view->preview = false;
        }

        $currentFolder = false;
        $parents = $itemDao->getFolders();
        if (count($parents) == 1) {
            $currentFolder = $parents[0];
        } elseif (isset($this->userSession->recentFolders)) {
            foreach ($this->userSession->recentFolders as $recent) {
                foreach ($parents as $parent) {
                    if ($parent->getKey() == $recent) {
                        $currentFolder = $parent;
                        break;
                    }
                }
            }
            if ($currentFolder === false && count($parents) > 0) {
                $currentFolder = $parents[0];
            }
        } elseif (count($parents) > 0) {
            $currentFolder = $parents[0];
        }
        $this->view->currentFolder = $currentFolder;
        $parent = $currentFolder;

        $breadcrumbs = array();
        while ($parent !== false) {
            if (strpos($parent->getName(), 'community') !== false && $this->Folder->getCommunity($parent) !== false
            ) {
                $breadcrumbs[] = array('type' => 'community', 'object' => $this->Folder->getCommunity($parent));
            } elseif (strpos($parent->getName(), 'user') !== false && $this->Folder->getUser($parent) !== false
            ) {
                $breadcrumbs[] = array('type' => 'user', 'object' => $this->Folder->getUser($parent));
            } else {
                $breadcrumbs[] = array('type' => 'folder', 'object' => $parent);
            }
            $parent = $parent->getParent();
        }
        $this->Component->Breadcrumb->setBreadcrumbHeader(array_reverse($breadcrumbs), $this->view);

        $folders = array();
        $parents = $itemDao->getFolders();
        foreach ($parents as $parent) {
            if ($this->Folder->policyCheck($parent, $this->userSession->Dao, MIDAS_POLICY_READ)
            ) {
                $folders[] = $parent;
            }
        }
        $this->view->folders = $folders;

        $this->view->json['item'] = $itemDao->toArray();
        $this->view->json['item']['isAdmin'] = $this->view->isAdmin;
        $this->view->json['item']['isModerator'] = $this->view->isModerator;
        $this->view->json['item']['message']['delete'] = $this->t('Delete');
        $this->view->json['item']['message']['sharedItem'] = $this->t(
            'This item is currrently shared by other folders and/or communities. Deletion will make it disappear in all these folders and/or communitites. '
        );
        $this->view->json['item']['message']['deleteMessage'] = $this->t(
            'Do you really want to delete this item? It cannot be undone.'
        );
        $this->view->json['item']['message']['deleteMetadataMessage'] = $this->t(
            'Do you really want to delete this metadata? It cannot be undone.'
        );
        $this->view->json['item']['message']['deleteItemrevisionMessage'] = $this->t(
            'Do you really want to delete this revision? It cannot be undone.'
        );
        $this->view->json['item']['message']['deleteBitstreamMessage'] = $this->t(
            'Do you really want to delete this bitstream? It cannot be undone.'
        );
        $this->view->json['item']['message']['duplicate'] = $this->t('Duplicate Item');
        $this->view->json['modules'] = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_ITEM_VIEW_JSON',
            array('item' => $itemDao)
        );
    }

    /**
     * Edit an item
     *
     * @throws Zend_Exception on invalid itemId and incorrect access permission
     */
    public function editAction()
    {
        $this->disableLayout();
        $item_id = $this->getParam('itemId');
        $item = $this->Item->load($item_id);
        if (!isset($item_id)) {
            throw new Zend_Exception("Please set the itemId.");
        } elseif ($item === false) {
            throw new Zend_Exception("The item doesn t exist.");
        } elseif (!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_WRITE)
        ) {
            throw new Zend_Exception("Permissions error.");
        }

        if ($this->_request->isPost()) {
            $updateBitstream = $this->getParam('updateBitstreamName');
            $name = $this->getParam('name');
            $description = $this->getParam('description');
            $license = $this->getParam('licenseSelect');

            $revision = $this->ItemRevision->getLatestRevision($item);

            if ($revision != false) {
                $revision->setLicenseId($license);
                $this->ItemRevision->save($revision);
                if ($updateBitstream) {
                    $bitstreams = $revision->getBitstreams();
                    if ((count($bitstreams) == 1) && (strlen($name) > 0)) {
                        $bitstream = $bitstreams[0];
                        $bitstream->setName($name);
                        $this->Bitstream->save($bitstream);
                    }
                }
            }
            if (strlen($name) > 0) {
                $item->setName($name);
            }
            $item->setDescription($description);
            $this->Item->save($item, true);
            $this->redirect('/item/'.$item->getKey());
        }

        $this->view->itemDao = $item;
        $form = $this->Form->Item->createEditForm();
        $formArray = $this->getFormAsArray($form);
        $formArray['name']->setValue($item->getName());
        $formArray['description']->setValue($item->getDescription());
        $this->view->form = $formArray;

        $this->view->allLicenses = $this->License->getAll();
        $revision = $this->ItemRevision->getLatestRevision($item);
        $this->view->displayUpdateBitstream = "none";
        if ($revision != false) {
            $this->view->selectedLicense = $revision->getLicenseId();
            $bitstreams = $revision->getBitstreams();
            if (count($bitstreams) == 1) {
                $this->view->displayUpdateBitstream = "block";
            }
        }
    }

    /**
     * Delete an item
     *
     * @throws Zend_Exception on invalid itemId and incorrect access permission
     */
    public function deleteAction()
    {
        $this->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $itemId = $this->getParam('itemId');
        if (!isset($itemId) || !is_numeric($itemId)) {
            throw new Zend_Exception("itemId should be a number");
        }
        $itemDao = $this->Item->load($itemId);
        if ($itemDao === false || !$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception("This item doesn't exist or you don't have the permissions.");
        }
        $parents = $itemDao->getFolders();

        $this->Item->delete($itemDao);

        if (count($parents) > 0) {
            $this->redirect('/folder/'.$parents[0]->getKey());
        } else {
            $this->redirect('/');
        }
    }

    /**
     * Delete an itemrevision
     *
     * @throws Zend_Exception on invalid itemId and incorrect access permission
     */
    public function deleteitemrevisionAction()
    {
        // load item and check permissions
        $itemId = $this->getParam('itemId');
        if (!isset($itemId) || !is_numeric($itemId)) {
            throw new Zend_Exception("itemId should be a number");
        }
        $itemDao = $this->Item->load($itemId);
        if ($itemDao === false || !$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception("This item doesn't exist or you don't have the permissions.");
        }

        // load itemrevision, ensure it exists
        $itemRevisionId = $this->getParam('itemrevisionId');
        if (!isset($itemRevisionId) || !is_numeric($itemRevisionId)) {
            throw new Zend_Exception("itemrevisionId should be a number");
        }
        $itemRevisionDao = $this->ItemRevision->load($itemRevisionId);
        if ($itemRevisionDao === false) {
            throw new Zend_Exception("This item revision doesn't exist.");
        }

        $this->Item->removeRevision($itemDao, $itemRevisionDao);

        // redirect to item view action
        $this->redirect('/item/'.$itemId);
    }

    /**
     * Edit an bitstream
     *
     * @throws Zend_Exception on invalid itemId/bitstreamId and incorrect access permission
     */
    public function editbitstreamAction()
    {
        $this->disableLayout();
        // load item and check permissions
        $bitstreamId = $this->getParam('bitstreamId');
        if (!isset($bitstreamId) || !is_numeric($bitstreamId)) {
            throw new Zend_Exception("bitstreamId should be a number");
        }
        $bitstreamDao = $this->Bitstream->load($bitstreamId);
        if ($bitstreamDao === false) {
            throw new Zend_Exception("This bitstream doesn't exist.");
        }
        $itemDao = $bitstreamDao->getItemrevision()->getItem();
        if ($itemDao === false || !$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_WRITE)
        ) {
            throw new Zend_Exception("This item doesn't exist or you don't have the permissions.");
        }

        if ($this->_request->isPost()) {
            $name = $this->getParam('name');
            $mimetype = $this->getParam('mimetype');

            if (strlen($name) > 0) {
                $bitstreamDao->setName($name);
            }
            $bitstreamDao->setMimetype($mimetype);
            $this->Bitstream->save($bitstreamDao);
            $this->redirect('/item/'.$itemDao->getKey());
        }

        $this->view->bitstreamDao = $bitstreamDao;
        $form = $this->Form->Item->createEditBitstreamForm();
        $formArray = $this->getFormAsArray($form);
        $formArray['name']->setValue($bitstreamDao->getName());
        $formArray['mimetype']->setValue($bitstreamDao->getMimetype());
        $this->view->bitstreamform = $formArray;
    }

    /**
     * Delete a bitstream
     *
     * @throws Zend_Exception on invalid itemId/bitstreamId and incorrect access permission
     */
    public function deletebitstreamAction()
    {
        $this->disableLayout();
        $this->disableView();
        // load item and check permissions
        $bitstreamId = $this->getParam('bitstreamId');
        if (!isset($bitstreamId) || !is_numeric($bitstreamId)) {
            throw new Zend_Exception("bitstreamId should be a number");
        }
        $bitstreamDao = $this->Bitstream->load($bitstreamId);
        if ($bitstreamDao === false) {
            throw new Zend_Exception("This bitstream doesn't exist.");
        }
        $itemDao = $bitstreamDao->getItemrevision()->getItem();
        if ($itemDao === false || !$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception("This item doesn't exist or you don't have the permissions.");
        }

        $this->Bitstream->delete($bitstreamDao);

        if (!$this->_request->isXmlHttpRequest()) {
            $this->redirect('/item/'.$itemDao->getKey());
        } else {
            echo JsonComponent::encode(true);
        }
    }

    /**
     * Merge items
     *
     * @throws Zend_Exception on invalid item name and incorrect access permission
     */
    public function mergeAction()
    {
        $this->disableLayout();
        $this->disableView();

        $itemIds = $this->getParam('items');
        $name = $this->getParam('name');
        $outputItemId = $this->getParam('outputItemId');
        if (empty($name) && $name !== '0') {
            throw new Zend_Exception('Please set a name');
        }
        $itemIds = explode('-', $itemIds);
        if ($this->progressDao) {
            $this->progressDao->setMaximum(count($itemIds));
            $this->Progress->save($this->progressDao);
        }

        $mainItem = $this->Item->mergeItems($itemIds, $name, $this->userSession->Dao, $this->progressDao);

        if (isset($outputItemId)) {
            echo $mainItem->getKey();
        } elseif ($this->_request->isXmlHttpRequest()) {
            echo JsonComponent::encode(array('redirect' => $this->view->webroot.'/item/'.$mainItem->getKey()));
        } else {
            $this->redirect('/item/'.$mainItem->getKey());
        }
    }

    /**
     * Check if an item is shared
     *
     * ajax function which checks if an item is shared in other folder/community
     *
     * @throws Zend_Exception on non-ajax call
     */
    public function checksharedAction()
    {
        $this->disableLayout();
        $this->disableView();
        $itemId = $this->getParam("itemId");
        $itemDao = $this->Item->load($itemId);
        $shareCount = count($itemDao->getFolders());
        $ifShared = false;
        if ($shareCount > 1) {
            $ifShared = true;
        }

        echo JsonComponent::encode($ifShared);
    }

    /**
     * ajax function which checks if a metadata value is defined for a given
     * item, itemrevision, metadatatype, element, and qualifier.
     *
     * @param itemId
     * @param itemrevision
     * @param metadatatype
     * @param element
     * @param qualifier
     * @throws Zend_Exception
     */
    public function getmetadatavalueexistsAction()
    {
        $this->disableLayout();
        $this->disableView();
        $itemId = $this->getParam('itemId');
        $itemRevisionNumber = $this->getParam('$itemrevision');
        $metadatatype = $this->getParam('metadatatype');
        $element = $this->getParam('element');
        $qualifier = $this->getParam('qualifier');
        $metadataDao = $this->Metadata->getMetadata($metadatatype, $element, $qualifier);
        if ($metadataDao == false) {
            $metadataValueExists = array("exists" => 0);
        } else {
            $itemDao = $this->Item->load($itemId);
            if ($itemDao === false) {
                throw new Zend_Exception("This item doesn't exist.", 404);
            }
            if (isset($itemRevisionNumber)) {
                $metadataItemRevision = $this->Item->getRevision($itemDao, $itemRevisionNumber);
            } else {
                $metadataItemRevision = $this->Item->getLastRevision($itemDao);
            }
            $metadataDao->setItemrevisionId($metadataItemRevision->getKey());
            if ($this->Metadata->getMetadataValueExists($metadataDao)) {
                $exists = 1;
            } else {
                $exists = 0;
            }
            $metadataValueExists = array("exists" => $exists);
        }
        echo JsonComponent::encode($metadataValueExists);
    }

    /**
     * Call this to download the thumbnail for the item.  Should only be called if the item has a thumbnail;
     * otherwise the request produces no output.
     *
     * @param itemId The item whose thumbnail you wish to download
     * @throws Zend_Exception
     */
    public function thumbnailAction()
    {
        $itemId = $this->getParam('itemId');
        if (!isset($itemId)) {
            throw new Zend_Exception('Must pass an itemId parameter');
        }
        $item = $this->Item->load($itemId);
        if (!$item) {
            throw new Zend_Exception('Invalid itemId', 404);
        }
        if (!$this->Item->policyCheck($item, $this->userSession->Dao)) {
            throw new Zend_Exception('Invalid policy', 403);
        }
        $this->disableLayout();
        $this->disableView();
        if ($item->getThumbnailId() !== null) {
            $bitstream = $this->Bitstream->load($item->getThumbnailId());

            /** @var DownloadBitstreamComponent $downloadBitstreamComponent */
            $downloadBitstreamComponent = MidasLoader::loadComponent('DownloadBitstream');
            $downloadBitstreamComponent->download($bitstream);
        }
    }
}
