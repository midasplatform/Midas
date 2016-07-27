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

/** AJAX request for the admin Controller. */
class BrowseController extends AppController
{
    public $_models = array('User', 'Community', 'Folder', 'Item', 'ItemRevision');
    public $_daos = array('User', 'Community', 'Folder', 'Item');
    public $_components = array('Date', 'Utility', 'Sortdao');

    /** Init Controller */
    public function init()
    {
        $this->view->activemenu = 'browse'; // set the active menu
    }

    /** Index Action */
    public function indexAction()
    {
        $this->view->Date = $this->Component->Date;

        $this->view->header = $this->t('Explore');

        $this->view->itemThumbnails = $this->Item->getRandomThumbnails($this->userSession->Dao, 0, 12, true);

        $this->view->items = $this->Item->getMostPopulars($this->userSession->Dao, 30);

        $this->view->nUsers = $this->User->getCountAll();
        $this->view->nCommunities = $this->Community->getCountAll();
        $this->view->nItems = $this->Item->getCountAll();

        $this->view->json['community']['titleCreateLogin'] = $this->t('Please log in');
        $this->view->json['community']['contentCreateLogin'] = $this->t(
            'You need to be logged in to be able to create a community.'
        );
    }

    /** move or copy selected element */
    public function movecopyAction()
    {
        $copytype = $this->getParam('copytype');
        if (isset($copytype) && $copytype == 'reference') {
            $shareSubmit = true;
        }
        $duplicateSubmit = $this->getParam('duplicateElement');
        $moveSubmit = $this->getParam('moveElement');

        $share = $this->getParam('share');
        $duplicate = $this->getParam('duplicate');
        $move = $this->getParam('move');

        // used for movecopyAction
        if (isset($moveSubmit) || isset($shareSubmit) || isset($duplicateSubmit)) {
            $elements = explode(';', $this->getParam('elements'));
            $destination = $this->getParam('destination');
            $ajax = $this->getParam('ajax');
            $folderIds = explode('-', $elements[0]);
            $itemIds = explode('-', $elements[1]);
            $folders = $this->Folder->load($folderIds);
            $items = $this->Item->load($itemIds);
            $destinationFolder = $this->Folder->load($destination);
            if (empty($folders) && empty($items)) {
                throw new Zend_Exception('No element selected');
            }
            if ($destinationFolder == false) {
                throw new Zend_Exception('Unable to load destination');
            }
            if (!$this->Folder->policyCheck($destinationFolder, $this->userSession->Dao, MIDAS_POLICY_WRITE)
            ) {
                throw new Zend_Exception('Write permission required into the destination folder');
            }

            // Folders can only be moved, not shared or duplicated
            if (isset($moveSubmit)) {
                foreach ($folders as $folder) {
                    if (!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
                    ) {
                        throw new Zend_Exception('You must own a folder in order to move it');
                    }
                    $this->Folder->move($folder, $destinationFolder);
                }
            }

            $sourceFolderIds = array();
            foreach ($items as $item) {
                if (isset($shareSubmit)) {
                    if (!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ)
                    ) {
                        throw new Zend_Exception('You must have read permission on an item to share it');
                    }
                    foreach ($item->getFolders() as $parentFolder) {
                        $folderId = $parentFolder->getKey();
                        array_push($sourceFolderIds, $folderId);
                    }
                    if (in_array($destinationFolder->getKey(), $sourceFolderIds)) {
                        $this->redirect('/item/'.$item->getKey());
                    } else {
                        // Do not update item name in item share action
                        $this->Folder->addItem($destinationFolder, $item, false);
                        $this->Item->addReadonlyPolicy($item, $destinationFolder);
                    }
                } elseif (isset($duplicateSubmit)) {
                    if (!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ)
                    ) {
                        throw new Zend_Exception('You must have read permission on an item to duplicate it');
                    }
                    $this->Item->duplicateItem($item, $this->userSession->Dao, $destinationFolder);
                } else { // moveSubmit, Move item(s)
                    if (!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
                    ) {
                        throw new Zend_Exception('You must own an item in order to move it');
                    }
                    $from = $this->getParam('from');
                    $fromFolder = $from ? $this->Folder->load($from) : null;
                    if ($destinationFolder == false) {
                        throw new Zend_Exception('Unable to load destination');
                    }
                    if ($from && $fromFolder == false) {
                        throw new Zend_Exception('Unable to load move from folder');
                    }
                    if (!$from) { // make sure item has only one parent in batch case
                        $parents = $item->getFolders();
                        if (count($parents) != 1
                        ) { // if item has multiple parents, we don't do the move in batch case
                            continue;
                        }
                        $fromFolder = $parents[0];
                    }

                    if ($destinationFolder->getKey() != $fromFolder->getKey()) {
                        $this->Folder->addItem($destinationFolder, $item);
                        $this->Item->copyParentPolicies($item, $destinationFolder);
                        $this->Folder->removeItem($fromFolder, $item);
                    }
                }
            }
            // Drag-and-drop actions
            if (isset($ajax)) {
                $this->disableLayout();
                $this->disableView();
                echo JsonComponent::encode(array(true, $this->t('Changes saved')));

                return;
            }
            $this->redirect('/folder/'.$destinationFolder->getKey());

            return;
        }

        $this->disableLayout();

        // Used for moveCopyForm (movecopy.phtml)
        if (isset($share) || isset($duplicate) || isset($move)) {
            $folderIds = $this->getParam('folders');
            $itemIds = $this->getParam('items');
            $this->view->folderIds = $folderIds;
            $this->view->itemIds = $itemIds;
            $folderIds = explode('-', $folderIds);
            $itemIds = explode('-', $itemIds);
            $folders = $this->Folder->load($folderIds);
            $items = $this->Item->load($itemIds);
            if (empty($folders) && empty($items)) {
                throw new Zend_Exception('No element selected');
            }
            if (!$this->logged) {
                throw new Zend_Exception(MIDAS_LOGIN_REQUIRED, 403);
            }
            $this->view->folders = $folders;
            $this->view->items = $items;
            if (count($items) == 1) {
                $this->view->referenceMessage = 'Create a reference to the existing item';
                $this->view->copyMessage = 'Copy the existing item into a new item';
            } else {
                $this->view->referenceMessage = 'Create references to the existing items';
                $this->view->copyMessage = 'Copy the existing items into new items';
            }
            if (isset($share)) {
                $this->view->shareEnabled = true;
            } elseif (isset($duplicate)) {
                $this->view->duplicateEnabled = true;
            } else { // isset($move)
                $this->view->moveEnabled = true;
                $from = $this->getParam('from');
                $this->view->from = $from;
            }
        } else { // isset($select)
            $this->view->selectEnabled = true;
        }

        $communities = $this->User->getUserCommunities($this->userSession->Dao);
        $communities = array_merge($communities, $this->Community->getPublicCommunities());
        $this->view->Date = $this->Component->Date;

        $this->Component->Sortdao->field = 'name';
        $this->Component->Sortdao->order = 'asc';
        usort($communities, array($this->Component->Sortdao, 'sortByName'));
        $communities = $this->Component->Sortdao->arrayUniqueDao($communities);

        $this->view->user = $this->userSession->Dao;
        $this->view->communities = $communities;
    }

    /** Ajax element used to select an item */
    public function selectitemAction()
    {
        $this->disableLayout();

        $this->view->selectEnabled = true;

        $communities = $this->User->getUserCommunities($this->userSession->Dao);
        $communities = array_merge($communities, $this->Community->getPublicCommunities());
        $this->view->Date = $this->Component->Date;

        $this->Component->Sortdao->field = 'name';
        $this->Component->Sortdao->order = 'asc';
        usort($communities, array($this->Component->Sortdao, 'sortByName'));
        $communities = $this->Component->Sortdao->arrayUniqueDao($communities);

        $this->view->user = $this->userSession->Dao;
        $this->view->communities = $communities;
    }

    /** Ajax element used to select a folder */
    public function selectfolderAction()
    {
        $this->disableLayout();
        $policy = $this->getParam('policy');

        $communities = $this->User->getUserCommunities($this->userSession->Dao);

        if (isset($policy) && $policy == 'read') {
            $policy = MIDAS_POLICY_READ;
            $communities = array_merge($communities, $this->Community->getPublicCommunities());
        } else {
            $policy = MIDAS_POLICY_WRITE;
        }

        $this->view->selectEnabled = true;

        $this->view->Date = $this->Component->Date;
        $this->view->policy = $policy;

        $this->Component->Sortdao->field = 'name';
        $this->Component->Sortdao->order = 'asc';
        usort($communities, array($this->Component->Sortdao, 'sortByName'));
        $communities = $this->Component->Sortdao->arrayUniqueDao($communities);

        $this->view->user = $this->userSession->Dao;
        $this->view->communities = $communities;
    }

    /**
     * Get the children of a set of folders (ajax).  Used
     * by the tree table when a folder is expanded or "show more results" is clicked.
     *
     * @param folders List of folder ids whose children should be fetched, separated by -
     * @param [sort] Sort field: (name | size | date), default is name
     * @param [sortdir] Sort direction: (asc | desc), default is asc
     * @param [folderOffset] The offset into the list of child folders, default is 0
     * @param [itemOffset] The offset into the list of child items, default is 0
     * @param [limit] The page size, default is unlimited
     */
    public function getfolderscontentAction()
    {
        $this->disableLayout();
        $this->disableView();

        $folderIds = $this->getParam('folders');
        $sort = $this->getParam('sort', 'name');
        $sortdir = $this->getParam('sortdir', 'asc');
        $foldersort = 'name';
        $foldersortdir = $sortdir;
        $itemsort = 'name';
        $itemsortdir = $sortdir;
        if ($sort == 'size') {
            $itemsort = 'sizebytes';
            $itemsortdir = $sortdir;
        } elseif ($sort == 'date') {
            $foldersort = 'date_update';
            $itemsort = 'date_update';
            $foldersortdir = $sortdir;
            $itemsortdir = $sortdir;
        }

        $folderOffset = (int) $this->getParam('folderOffset', 0);
        $itemOffset = (int) $this->getParam('itemOffset', 0);
        $limit = (int) $this->getParam('limit', -1);

        if (!isset($folderIds)) {
            throw new Zend_Exception('Please set the folder Id');
        }
        $folderIds = explode('-', $folderIds);
        $parents = $this->Folder->load($folderIds);
        if (empty($parents)) {
            throw new Zend_Exception("Folder doesn't exist");
        }

        // We always show folders above items in the child list; fetch them first
        $showMoreLink = false;
        $folders = $this->Folder->getChildrenFoldersFiltered(
            $parents,
            $this->userSession->Dao,
            MIDAS_POLICY_READ,
            $foldersort,
            $foldersortdir,
            $limit + 1,
            $folderOffset
        );
        $folderCount = count($folders);
        $folderOffset += min($limit, $folderCount);
        if ($limit > 0 && $folderCount > $limit) { // If we have more folder children than the page allows, no need to fetch items this pass
            array_pop($folders); // remove the last element since it is one over the limit
            $items = array();
            $showMoreLink = true;
        } else {
            $itemLimit = $limit - $folderCount;
            $items = $this->Folder->getItemsFiltered(
                $parents,
                $this->userSession->Dao,
                MIDAS_POLICY_READ,
                $itemsort,
                $itemsortdir,
                $itemLimit + 1,
                $itemOffset
            );
            $itemCount = count($items);
            if ($limit > 0 && $itemCount > $itemLimit) {
                array_pop($items);
                --$itemCount;
                $showMoreLink = true;
            }
            $itemOffset += min($limit, $itemCount);
        }

        $jsonContent = array();
        foreach ($parents as $parent) {
            $jsonContent[$parent->getKey()]['folders'] = array();
            $jsonContent[$parent->getKey()]['items'] = array();
            $jsonContent[$parent->getKey()]['showMoreLink'] = $showMoreLink;
            $jsonContent[$parent->getKey()]['folderOffset'] = $folderOffset;
            $jsonContent[$parent->getKey()]['itemOffset'] = $itemOffset;
        }
        foreach ($folders as $folder) {
            $tmp = array();
            $tmp['folder_id'] = $folder->getFolderId();
            $tmp['name'] = $folder->getName();
            $tmp['date_update'] = $this->Component->Date->ago($folder->getDateUpdate(), true);
            $tmp['privacy_status'] = $folder->privacy_status;
            $jsonContent[$folder->getParentId()]['folders'][] = $tmp;
            unset($tmp);
        }
        foreach ($items as $item) {
            $tmp = array();
            $tmp['item_id'] = $item->getItemId();
            $tmp['name'] = $item->getName();
            $tmp['parent_id'] = $item->parent_id;
            $tmp['date_update'] = $this->Component->Date->ago($item->getDateUpdate(), true);
            $tmp['size'] = $this->Component->Utility->formatSize($item->getSizebytes());
            $tmp['privacy_status'] = $item->privacy_status;
            $jsonContent[$item->parent_id]['items'][] = $tmp;
            unset($tmp);
        }
        echo JsonComponent::encode($jsonContent);
    }

    /** get getfolders Items' size */
    public function getfolderssizeAction()
    {
        $this->disableLayout();
        $this->disableView();
        $folderIds = $this->getParam('folders');
        if (!isset($folderIds)) {
            echo '[]';

            return;
        }
        $folderIds = explode('-', $folderIds);
        $folders = $this->Folder->load($folderIds);
        $folders = $this->Folder->getSizeFiltered($folders, $this->userSession->Dao);
        $return = array();
        foreach ($folders as $folder) {
            $return[] = array(
                'id' => $folder->getKey(),
                'count' => $folder->count,
                'size' => $this->Component->Utility->formatSize($folder->size),
            );
        }
        echo JsonComponent::encode($return);
    }

    /** get element info (ajax function for the tree table) */
    public function getelementinfoAction()
    {
        $this->disableLayout();
        $this->disableView();
        $element = $this->getParam('type');
        $id = $this->getParam('id');
        if (!isset($id) || !isset($element)) {
            throw new Zend_Exception('Please double check the parameters');
        }
        $jsonContent = array('type' => $element);
        switch ($element) {
            case 'community':
                $community = $this->Community->load($id);
                if (!$this->Community->policyCheck($community, $this->userSession->Dao, MIDAS_POLICY_READ)
                ) {
                    throw new Zend_Exception('User does not have read permission on the community');
                }
                $jsonContent = array_merge($jsonContent, $community->toArray());
                $jsonContent['creation'] = $this->Component->Date->formatDate(strtotime($community->getCreation()));
                $members = $community->getMemberGroup()->getUsers();
                $jsonContent['members'] = count($members);
                break;
            case 'folder':
                $folder = $this->Folder->load($id);
                if (!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_READ)
                ) {
                    throw new Zend_Exception('User does not have read permission on the folder');
                }
                $jsonContent = array_merge($jsonContent, $folder->toArray());
                $jsonContent['creation'] = $this->Component->Date->formatDate(strtotime($jsonContent['date_creation']));
                $jsonContent['updated'] = $this->Component->Date->formatDate(strtotime($jsonContent['date_update']));
                $sizeList = $this->Folder->getSizeFiltered($folder, $this->userSession->Dao, MIDAS_POLICY_READ);
                $jsonContent['sizebytes'] = $sizeList[0]->size;
                $jsonContent['size'] = $this->Component->Utility->formatSize($jsonContent['sizebytes']);
                if (!isset($this->userSession->recentFolders)) {
                    $this->userSession->recentFolders = array();
                }
                array_push($this->userSession->recentFolders, $folder->getKey());
                if (count($this->userSession->recentFolders) > 5) {
                    array_shift($this->userSession->recentFolders);
                }
                break;
            case 'item':
                $item = $this->Item->load($id);
                if (!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ)
                ) {
                    throw new Zend_Exception('User does not have read permission on the item');
                }
                $jsonContent = array_merge($jsonContent, $item->toArray());
                $itemRevision = $this->Item->getLastRevision($item);
                if (isset($itemRevision) && $itemRevision !== false) {
                    $jsonContent['creation'] = $this->Component->Date->formatDate(strtotime($itemRevision->getDate()));
                    $jsonContent['uploaded'] = $itemRevision->getUser()->toArray();
                    $jsonContent['revision'] = $itemRevision->toArray();
                    $jsonContent['nbitstream'] = count($itemRevision->getBitstreams());
                } else {
                    $jsonContent['creation'] = $this->Component->Date->formatDate(strtotime($item->getDateCreation()));
                    $jsonContent['norevisions'] = true;
                }
                $jsonContent['type'] = 'item';
                $jsonContent['size'] = $this->Component->Utility->formatSize($jsonContent['sizebytes']);
                break;
            default:
                throw new Zend_Exception('Please select the right type of element.');
                break;
        }
        $jsonContent['translation']['Created'] = $this->t('Created');
        $jsonContent['translation']['File'] = $this->t('File');
        $jsonContent['translation']['Uploaded'] = $this->t('Uploaded by');
        $jsonContent['translation']['Private'] = $this->t('This community is private');
        echo JsonComponent::encode($jsonContent);
    }

    /**
     * The first time a user clicks on an item or folder in the tree table, it makes
     * an ajax request to this method, which returns the maximum policy value for that
     * item or folder for the given user. This value should be cached in the DOM to avoid
     * repeated requests for the same resource.
     *
     * @param id The id of the resource
     * @param type The type of the resource: (folder | item)
     * @return JSON object with the "policy" field set to the max policy on the resource.
     *              Throws exception if the user has no access to the resource
     * @throws Zend_Exception
     */
    public function getmaxpolicyAction()
    {
        $this->disableLayout();
        $this->disableView();

        $id = $this->getParam('id');
        $type = $this->getParam('type');

        if (!isset($id) || !isset($type)) {
            throw new Zend_Exception('Must pass id and type parameters');
        }
        switch (strtolower($type)) {
            case 'folder':
                $maxpolicy = $this->Folder->getMaxPolicy($id, $this->userSession->Dao);
                break;
            case 'item':
                $maxpolicy = $this->Item->getMaxPolicy($id, $this->userSession->Dao);
                break;
            default:
                throw new Zend_Exception('Parameter type must be either item or folder');
        }
        if ($maxpolicy < 0) {
            throw new Zend_Exception('You have no access to '.$type.' '.$id, 403);
        }
        echo JsonComponent::encode(array('policy' => $maxpolicy));
    }

    /**
     * Delete a set of folders and items. Called by ajax from common.browser.js.
     *
     * @param folders A list of folder ids separated by '-'
     * @param items A list of item ids separated by '-'
     * @return Replies with a json object of the form:
     *                 {success: {folders: [<id>, <id>, ...], items: [<id>, <id>, ...]},
     *                 failure: {folders: [<id>, <id>, ...], items: [<id>, <id>, ...]}}
     *                 Denoting which deletes succeeded and which failed.  Invalid ids will be considered
     *                 already deleted and are thus returned as successful
     */
    public function deleteAction()
    {
        if (!$this->logged) {
            throw new Zend_Exception('You must be logged in to delete resources.');
        }
        UtilityComponent::disableMemoryLimit();
        $this->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $folderIds = $this->getParam('folders');
        $itemIds = $this->getParam('items');

        $resp = array(
            'success' => array('folders' => array(), 'items' => array()),
            'failure' => array('folders' => array(), 'items' => array()),
        );
        $folderIds = explode('-', $folderIds);
        $itemIds = explode('-', $itemIds);

        foreach ($folderIds as $folderId) {
            if ($folderId == '') {
                continue;
            }
            $folder = $this->Folder->load($folderId);
            if (!$folder) {
                $resp['success']['folders'][] = $folderId; // probably deleted by a parent delete
                continue;
            }

            if ($this->Folder->policyCheck(
                    $folder,
                    $this->userSession->Dao,
                    MIDAS_POLICY_ADMIN
                ) && $this->Folder->isDeleteable($folder)
            ) {
                $this->Folder->delete($folder);
                $resp['success']['folders'][] = $folderId;
            } else {
                $resp['failure']['folders'][] = $folderId; // permission failure
            }
        }

        foreach ($itemIds as $itemId) {
            if ($itemId == '') {
                continue;
            }
            $item = $this->Item->load($itemId);
            if (!$item) {
                $resp['success']['items'][] = $itemId; // probably deleted by a parent delete
                continue;
            }

            if ($this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
            ) {
                $this->Item->delete($item);
                $resp['success']['items'][] = $itemId;
            } else {
                $resp['failure']['items'][] = $itemId; // permission failure
            }
        }
        echo JsonComponent::encode($resp);
    }
}
