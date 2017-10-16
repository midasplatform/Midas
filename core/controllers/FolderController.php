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

/** Folder Controller */
class FolderController extends AppController
{
    public $_models = array('Folder', 'Folder', 'Item', 'Folderpolicygroup', 'Folderpolicyuser', 'Progress');
    public $_daos = array('Folder', 'Folder', 'Item');
    public $_components = array('Breadcrumb', 'Date');
    public $_forms = array('Folder');

    /** Init Controller */
    public function init()
    {
        $this->view->activemenu = ''; // set the active menu
        $actionName = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
        if (isset($actionName) && is_numeric($actionName)) {
            $this->forward('view', null, null, array('folderId' => $actionName));
        }
        $this->view->activemenu = 'browse'; // set the active menu
    }

    /**
     * Simply prints the name of the requested folder.  Used by the large download applet.
     */
    public function getnameAction()
    {
        $this->disableLayout();
        $this->disableView();
        $folderId = $this->getParam('id');
        if (!isset($folderId)) {
            throw new Zend_Exception('Must pass id parameter');
        }

        $validator = new Zend_Validate_Digits();
        if (!$validator->isValid($folderId)) {
            throw new Zend_Exception('Must specify an id parameter');
        }

        $folder = $this->Folder->load($folderId);
        if (!$folder) {
            throw new Zend_Exception('Invalid folderId', 404);
        }
        if (!$this->Folder->policyCheck($folder, $this->userSession->Dao)) {
            throw new Zend_Exception('Read permission required', 403);
        }
        echo $folder->getName();
    }

    /**
     * Echoes the children of the folder (filtered on permissions) for the large download applet.
     */
    public function javachildrenAction()
    {
        $this->disableLayout();
        $this->disableView();
        $folderId = $this->getParam('id');
        if (!isset($folderId)) {
            throw new Zend_Exception('Must pass id parameter');
        }

        $validator = new Zend_Validate_Digits();
        if (!$validator->isValid($folderId)) {
            throw new Zend_Exception('Must specify an id parameter');
        }

        $folder = $this->Folder->load($folderId);
        if (!$folder) {
            throw new Zend_Exception('Invalid folderId', 404);
        }
        if (!$this->Folder->policyCheck($folder, $this->userSession->Dao)) {
            throw new Zend_Exception('Read permission required', 403);
        }
        $childFolders = $this->Folder->getChildrenFoldersFiltered($folder, $this->userSession->Dao, MIDAS_POLICY_READ);
        $childItems = $this->Folder->getItemsFiltered($folder, $this->userSession->Dao, MIDAS_POLICY_READ);

        foreach ($childFolders as $childFolder) {
            echo 'f '.$childFolder->getKey().' '.$childFolder->getName()."\n";
        }
        foreach ($childItems as $childItem) {
            echo 'i '.$childItem->getKey().' '.$childItem->getName()."\n";
        }
    }

    /** Edit Folder (ajax) */
    public function editAction()
    {
        $this->disableLayout();
        $folder_id = $this->getParam('folderId');

        $validator = new Zend_Validate_Digits();
        if (!$validator->isValid($folder_id)) {
            throw new Zend_Exception('Must specify a folderId parameter');
        }

        $folder = $this->Folder->load($folder_id);
        if (!isset($folder_id)) {
            throw new Zend_Exception('Please set the folderId.');
        } elseif ($folder === false) {
            throw new Zend_Exception('The folder doesn t exist.');
        } elseif (!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_WRITE)
        ) {
            throw new Zend_Exception('Permissions error.');
        }

        if ($this->_request->isPost()) {
            $name = $this->getParam('name');

            // Check if folder with the same name already exists for the same parent
            if ($folder->getName() != $name && $this->Folder->getFolderExists($name, $folder->getParent())
            ) {
                throw new Zend_Exception('This name is already used');
            }

            $description = $this->getParam('description');
            $teaser = $this->getParam('teaser');

            if (strlen($name) > 0) {
                $folder->setName($name);
            }
            $folder->setDescription($description);
            if (strlen($teaser) < 251) {
                $folder->setTeaser($teaser);
            }

            $this->Folder->save($folder);
            $this->redirect('/folder/'.$folder->getKey());
        }

        $this->view->folderDao = $folder;
        $form = $this->Form->Folder->createEditForm();
        $formArray = $this->getFormAsArray($form);
        $formArray['name']->setValue($folder->getName());
        $formArray['description']->setValue($folder->getDescription());
        $formArray['teaser']->setValue($folder->getTeaser());
        $this->view->form = $formArray;
    }

    /** View Action */
    public function viewAction()
    {
        $this->view->Date = $this->Component->Date;
        $folder_id = $this->getParam('folderId');
        $folder = $this->Folder->load($folder_id);

        if (!$this->_request->isGet()) {
            throw new Zend_Exception('Only HTTP Get requests are accepted', 400);
        } elseif (!isset($folder_id)) {
            throw new Zend_Exception('Please set the folderId.');
        } elseif ($folder === false) {
            throw new Zend_Exception("The folder doesn't exist.", 404);
        } elseif (!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            throw new Zend_Exception('Invalid policy: no read access', 403);
        } else {
            $folders = $this->Folder->getChildrenFoldersFiltered($folder, $this->userSession->Dao, MIDAS_POLICY_READ);
            $items = $this->Folder->getItemsFiltered($folder, $this->userSession->Dao, MIDAS_POLICY_READ);
            foreach ($items as $key => $i) {
                $items[$key]->size = UtilityComponent::formatSize($i->getSizebytes());
            }

            $breadcrumbs = array(array('type' => 'folder', 'object' => $folder, 'open' => true, 'link' => false));
            $parent = $folder->getParent();
            while ($parent !== false) {
                if (strpos($parent->getName(), 'community') !== false && $this->Folder->getCommunity($parent) !== false
                ) {
                    $breadcrumbs[] = array('type' => 'community', 'object' => $this->Folder->getCommunity($parent));
                } elseif (strpos($parent->getName(), 'user') !== false && $this->Folder->getUser($parent) !== false
                ) {
                    $breadcrumbs[] = array('type' => 'user', 'object' => $this->Folder->getUser($parent));
                } else {
                    $breadcrumbs[] = array('type' => 'folder', 'object' => $parent, 'open' => false);
                }
                $parent = $parent->getParent();
            }
            $this->Component->Breadcrumb->setBreadcrumbHeader(array_reverse($breadcrumbs), $this->view);
        }

        if (!isset($this->userSession->recentFolders)) {
            $this->userSession->recentFolders = array();
        }
        array_push($this->userSession->recentFolders, $folder->getKey());
        if (count($this->userSession->recentFolders) > 5) {
            array_shift($this->userSession->recentFolders);
        }

        $this->Folder->incrementViewCount($folder);
        $this->view->mainFolder = $folder;
        $this->view->folders = $folders;
        $this->view->items = $items;
        $this->view->deleteable = $this->Folder->isDeleteable($folder);

        $this->view->isModerator = $this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_WRITE);
        $this->view->isAdmin = $this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_ADMIN);

        $this->view->title .= ' - '.$folder->getName();
        $this->view->metaDescription = substr($folder->getDescription(), 0, 160);
        $this->view->json['folder'] = $folder;
    }

    /**
     * Prompt the user to confirm deletion of a folder.
     *
     * @param folderId The id of the folder to be deleted
     */
    public function deletedialogAction()
    {
        $this->disableLayout();
        $folderId = $this->getParam('folderId');

        if (!isset($folderId)) {
            throw new Zend_Exception('Must pass folderId parameter');
        }
        $folder = $this->Folder->load($folderId);
        if ($folder === false) {
            throw new Zend_Exception('Invalid folderId', 404);
        } elseif (!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception('Admin permission required', 403);
        }
        $this->view->folder = $folder;
        $sizes = $this->Folder->getSizeFiltered(array($folder), $this->userSession->Dao);
        $this->view->sizeStr = UtilityComponent::formatSize($sizes[0]->size);
    }

    /** delete a folder (ajax action)*/
    public function deleteAction()
    {
        $this->disableLayout();
        $this->disableView();
        $folder_id = $this->getParam('folderId');
        $folder = $this->Folder->load($folder_id);
        if (!isset($folder_id)) {
            throw new Zend_Exception('Please set the folderId.');
        } elseif ($folder === false) {
            throw new Zend_Exception("The folder doesn't exist.");
        } elseif (!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception('Permissions error.');
        }

        // User cannot delete community's root folder, the default 'Public' folder and the default 'Private' folder
        if ($this->Folder->getCommunity($folder) != false) {
            throw new Zend_Exception('Community Root Folder. You cannot delete it.');
        }

        // User cannot delete its root folder, the default 'Public' folder and the default 'Private' folder
        if ($this->Folder->getUser($folder) != false) {
            throw new Zend_Exception('User Root Folder. You cannot delete it.');
        }

        if ($this->progressDao) {
            $this->progressDao->setMaximum($this->Folder->getRecursiveChildCount($folder) + 1);
            $this->progressDao->setMessage('Preparing to delete folder...');
            $this->Progress->save($this->progressDao);
        }
        UtilityComponent::disableMemoryLimit();
        $this->Folder->delete($folder, $this->progressDao);
        $folderInfo = $folder->toArray();
        echo JsonComponent::encode(array(true, $this->t('Changes saved'), $folderInfo));
    }

    /** remove an item from a folder (dialog,ajax only)*/
    public function removeitemAction()
    {
        $folder_id = $this->getParam('folderId');
        $item_id = $this->getParam('itemId');
        $folder = $this->Folder->load($folder_id);
        $item = $this->Item->load($item_id);

        if (!isset($folder_id)) {
            throw new Zend_Exception('Please set the folderId.');
        }
        if (!isset($item_id)) {
            throw new Zend_Exception('Please set the folderId.');
        } elseif ($folder === false) {
            throw new Zend_Exception("The folder doesn't exist.");
        } elseif ($item === false) {
            throw new Zend_Exception("The item doesn't exist.");
        } elseif (!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_WRITE)
        ) {
            throw new Zend_Exception('Write permission on folder required');
        } elseif (!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception(MIDAS_ADMIN_PRIVILEGES_REQUIRED);
        }

        $this->disableLayout();
        $this->disableView();
        $this->Folder->removeItem($folder, $item);
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
    }

    /** create a folder (dialog,ajax only)*/
    public function createfolderAction()
    {
        $this->disableLayout();
        $folder_id = $this->getParam('folderId');
        $folder = $this->Folder->load($folder_id);
        $form = $this->Form->Folder->createEditForm();
        $formArray = $this->getFormAsArray($form);
        $this->view->form = $formArray;
        if (!isset($folder_id)) {
            throw new Zend_Exception('Please set the folderId.');
        } elseif ($folder === false) {
            throw new Zend_Exception("The folder doesn't exist.");
        }

        if (!$this->Folder->policyCheck($folder, $this->userSession->Dao, MIDAS_POLICY_WRITE)
        ) {
            $this->disableView();
            echo '<div class="errorText">You do not have permission to create a folder here.</div>';

            return;
        }

        $this->view->parentFolder = $folder;
        if ($this->_request->isPost()) {
            $this->disableView();
            $createFolder = $this->getParam('createFolder');
            if (isset($createFolder)) {
                $name = $this->getParam('name');
                $description = $this->getParam('description') ? $this->getParam('description') : '';
                if (!isset($name)) {
                    echo JsonComponent::encode(array(false, $this->t('Error: name parameter required')));
                } else {
                    // Check if folder with the same name already exists for the same parent
                    if ($this->Folder->getFolderExists($name, $folder)) {
                        echo JsonComponent::encode(array(false, $this->t('This name is already used')));

                        return;
                    }
                    $new_folder = $this->Folder->createFolder($name, $description, $folder);

                    if ($new_folder == false) {
                        echo JsonComponent::encode(array(false, $this->t('Error')));
                    } else {
                        $policyGroup = $folder->getFolderpolicygroup();
                        $policyUser = $folder->getFolderpolicyuser();
                        foreach ($policyGroup as $policy) {
                            $group = $policy->getGroup();
                            $policyValue = $policy->getPolicy();
                            $this->Folderpolicygroup->createPolicy($group, $new_folder, $policyValue);
                        }
                        foreach ($policyUser as $policy) {
                            $user = $policy->getUser();
                            $policyValue = $policy->getPolicy();
                            $this->Folderpolicyuser->createPolicy($user, $new_folder, $policyValue);
                        }
                        if (!$this->Folder->policyCheck($new_folder, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
                        ) {
                            $this->Folderpolicyuser->createPolicy(
                                $this->userSession->Dao,
                                $new_folder,
                                MIDAS_POLICY_ADMIN
                            );
                        }
                        $newFolder_dateUpdate = $this->Component->Date->ago($new_folder->getDateUpdate(), true);
                        echo JsonComponent::encode(
                            array(
                                true,
                                $this->t('Changes saved'),
                                $folder->toArray(),
                                $new_folder->toArray(),
                                $newFolder_dateUpdate,
                            )
                        );
                    }
                }
            }
        }
    }
}
