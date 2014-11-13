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
 * Folder DAO.
 *
 * @method int getFolderId()
 * @method void setFolderId(int $folderId)
 * @method int getLeftIndex()
 * @method void setLeftIndex(int $leftIndex)
 * @method int getRightIndex()
 * @method void setRightIndex(int $rightIndex)
 * @method int getParentId()
 * @method void setParentId(int $parentId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method string getDateUpdate()
 * @method void setDateUpdate(string $dateUpdate)
 * @method string getDateCreation()
 * @method void setDateCreation(string $dateCreation)
 * @method int getView()
 * @method void setView(int $view)
 * @method string getTeaser()
 * @method void setTeaser(string $teaser)
 * @method int getPrivacyStatus()
 * @method void setPrivacyStatus(int $privacyStatus)
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method array getItems()
 * @method void setItems(array $items)
 * @method FolderpolicygroupDao getFolderpolicygroup()
 * @method void setFolderpolicygroup(FolderpolicygroupDao $folderPolicyGroup)
 * @method FolderpolicyuserDao getFolderpolicyuser()
 * @method void setFolderpolicyuser(FolderpolicyuserDao $folderPolicyUser)
 * @method array getFolders()
 * @method void setFolders(array $folders)
 * @method FolderDao getParent()
 * @method void setParent(FolderDao $parent)
 * @package Core\DAO
 */
class FolderDao extends AppDao
{
    /** @var string */
    public $_model = 'Folder';

    /**
     * Return the left index.
     *
     * @deprecated since 3.3.0
     * @return int
     */
    public function getLeftIndice()
    {
        return $this->getLeftIndex();
    }

    /**
     * Set the left index.
     *
     * @deprecated since 3.3.0
     * @param int $leftIndex left index
     */
    public function setLeftIndice($leftIndex)
    {
        $this->setLeftIndex($leftIndex);
    }

    /**
     * Return the right index.
     *
     * @deprecated since 3.3.0
     * @return int
     */
    public function getRightIndice()
    {
        return $this->getRightIndex();
    }

    /**
     * Set the right index.
     *
     * @deprecated since 3.3.0
     * @param int $rightIndex right index
     */
    public function setRightIndice($rightIndex)
    {
        $this->setRightIndex($rightIndex);
    }
}
