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

/** This class handles the upload of files into the different assetstores */
class UploadComponent extends AppComponent
{
    /**
     * Helper function to create the two-level hierarchy
     *
     * @param string $directorypath
     * @throws Zend_Exception
     */
    private function _createAssetstoreDirectory($directorypath)
    {
        if (!file_exists($directorypath)) {
            if (!mkdir($directorypath)) {
                throw new Zend_Exception("Cannot create directory: ".$directorypath);
            }
            chmod($directorypath, 0777);
        }
    }

    /**
     * Upload local bitstream
     *
     * @param BitstreamDao $bitstreamdao
     * @param AssetstoreDao $assetstoredao
     * @param bool $copy
     * @throws Zend_Exception
     */
    private function _uploadLocalBitstream($bitstreamdao, $assetstoredao, $copy = false)
    {
        // Check if the type of the assetstore is suitable
        if ($assetstoredao->getType() != MIDAS_ASSETSTORE_LOCAL) {
            throw new Zend_Exception("The assetstore type should be local to upload.");
        }

        // Check if the path of the assetstore exists on the server
        if (!is_dir($assetstoredao->getPath())) {
            throw new Zend_Exception("The assetstore path doesn't exist.");
        }

        // Check if the MD5 exists for the bitstream
        $checksum = $bitstreamdao->getChecksum();
        if (empty($checksum)) {
            throw new Zend_Exception("Checksum is not set.");
        }

        // If we already have a file of this checksum in any assetstore, we point to it
        /** @var BitstreamModel $bitstreamModel */
        $bitstreamModel = MidasLoader::loadModel('Bitstream');
        $existing = $bitstreamModel->getByChecksum($checksum);
        if ($existing) {
            if ($copy === false) {
                unlink($bitstreamdao->getPath()); // Remove the temporary uploaded file
            }
            $bitstreamdao->setPath($existing->getPath());
            $bitstreamdao->setAssetstoreId($existing->getAssetstoreId());

            return;
        }

        // Two-level hierarchy.
        $path = substr($checksum, 0, 2).'/'.substr($checksum, 2, 2).'/'.$checksum;
        $fullpath = $assetstoredao->getPath().'/'.$path;

        // Create the directories
        $currentdir = $assetstoredao->getPath().'/'.substr($checksum, 0, 2);
        $this->_createAssetstoreDirectory($currentdir);
        $currentdir .= '/'.substr($checksum, 2, 2);
        $this->_createAssetstoreDirectory($currentdir);

        if ($copy) {
            copy($bitstreamdao->getPath(), $fullpath);
        } else {
            rename($bitstreamdao->getPath(), $fullpath);
        }

        // Set the new path
        $bitstreamdao->setPath($path);
    }

    /**
     * Upload a bitstream
     *
     * @param BitstreamDao $bitstreamdao
     * @param AssetstoreDao $assetstoredao
     * @param bool $copy
     * @return bool
     * @throws Zend_Exception
     */
    public function uploadBitstream($bitstreamdao, $assetstoredao, $copy = false)
    {
        $assetstoretype = $assetstoredao->getType();
        switch ($assetstoretype) {
            case MIDAS_ASSETSTORE_LOCAL:
                $this->_uploadLocalBitstream($bitstreamdao, $assetstoredao, $copy);
                break;
            case MIDAS_ASSETSTORE_REMOTE:
                // Nothing to upload in that case, we return silently
                return true;
            default:
                break;
        }

        return true;
    }

    /**
     * Save upload item in the database
     *
     * @param UserDao $userDao
     * @param string $name
     * @param string $url
     * @param null|int $parent
     * @param int $sizebytes
     * @param string $checksum
     * @return ItemDao
     * @throws Zend_Exception
     */
    public function createLinkItem($userDao, $name, $url, $parent = null, $sizebytes = 0, $checksum = ' ')
    {
        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        /** @var FolderModel $folderModel */
        $folderModel = MidasLoader::loadModel('Folder');

        /** @var AssetstoreModel $assetstoreModel */
        $assetstoreModel = MidasLoader::loadModel('Assetstore');

        /** @var ItemRevisionModel $itemRevisionModel */
        $itemRevisionModel = MidasLoader::loadModel('ItemRevision');

        if ($userDao == null) {
            throw new Zend_Exception('Please log in');
        }

        if (is_numeric($parent)) {
            $parent = $folderModel->load($parent);
        }

        if ($parent == false || !$folderModel->policyCheck($parent, $userDao, MIDAS_POLICY_WRITE)
        ) {
            throw new Zend_Exception('Parent permissions errors');
        }

        Zend_Loader::loadClass('ItemDao', BASE_PATH.'/core/models/dao');
        $item = new ItemDao();
        $item->setName($name);
        $item->setDescription('');
        $item->setSizebytes($sizebytes);
        $item->setType(0);
        $item->setPrivacyStatus(MIDAS_PRIVACY_PRIVATE); // Must set this flag private initially
        $itemModel->save($item, false);

        $folderModel->addItem($parent, $item);
        $itemModel->copyParentPolicies($item, $parent /*, $feed */);

        Zend_Loader::loadClass('ItemRevisionDao', BASE_PATH.'/core/models/dao');
        $itemRevisionDao = new ItemRevisionDao();
        $itemRevisionDao->setChanges('Initial revision');
        $itemRevisionDao->setUser_id($userDao->getKey());
        $itemRevisionDao->setDate(date('Y-m-d H:i:s'));
        $itemRevisionDao->setLicenseId(null);
        $itemModel->addRevision($item, $itemRevisionDao);

        // Add bitstreams to the revision
        Zend_Loader::loadClass('BitstreamDao', BASE_PATH.'/core/models/dao');
        $bitstreamDao = new BitstreamDao();
        $bitstreamDao->setName($url);
        $bitstreamDao->setPath($url);
        $bitstreamDao->setMimetype('url');
        $bitstreamDao->setSizebytes($sizebytes);
        $bitstreamDao->setChecksum($checksum);

        $assetstoreDao = $assetstoreModel->getDefault();
        $bitstreamDao->setAssetstoreId($assetstoreDao->getKey());

        $itemRevisionModel->addBitstream($itemRevisionDao, $bitstreamDao);

        $this->getLogger()->debug('Link item created ('.$item->getName().', id='.$item->getKey().')');

        return $item;
    }

    /**
     * Save an uploaded file in the database as an item with a new revision
     *
     * @param UserDao $userDao The user who is uploading the item
     * @param string $name The name of the item
     * @param string $path The path of the uploaded file on disk
     * @param null|int $parent The id of the parent folder to create the item in
     * @param null|int $license [optional][default=null] License text for the item
     * @param string $fileChecksum [optional][default=''] If passed, will be used instead of calculating it ourselves
     * @param bool $copy [optional][default=false] Boolean value for whether to copy or just move the item into the assetstore
     * @param bool $revOnCollision [optional][default=false] Boolean value for whether to create a new revision on item name collision
     * @param null|int $fileSize If passed, will use it instead of calculating it ourselves
     * @param null|string $mimeType If passed, will use it instead of calculating it ourselves
     * @return ItemDao
     * @throws Zend_Exception
     */
    public function createUploadedItem(
        $userDao,
        $name,
        $path,
        $parent = null,
        $license = null,
        $fileChecksum = '',
        $copy = false,
        $revOnCollision = false,
        $fileSize = null,
        $mimeType = null
    ) {
        if ($userDao === null) {
            throw new Zend_Exception('Please log in');
        }

        /** @var FolderModel $folderModel */
        $folderModel = MidasLoader::loadModel('Folder');

        if (is_numeric($parent)) {
            $parent = $folderModel->load($parent);
        }

        if ($parent === false || !$folderModel->policyCheck($parent, $userDao, MIDAS_POLICY_WRITE)
        ) {
            throw new Zend_Exception('Parent permissions errors');
        }

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        /** @var ItempolicyuserModel $itemPolicyUserModel */
        $itemPolicyUserModel = MidasLoader::loadModel('Itempolicyuser');

        // Note the conditional inner assignment of $item if user has selected new revision on name collision.
        // This is done so that we can elegantly fall through to the new item clause unless both the new
        // revision on collision option is on and a collision has actually occurred.
        if ($revOnCollision && ($itemDao = $folderModel->getItemByName($parent, $name)) !== false
        ) {
            $changes = '';
            $this->getLogger()->info(
                'Item uploaded: revision overwrite ('.$itemDao->getName().', id='.$itemDao->getKey().')'
            );
        } else { // new item
            /** @var ItemDao $itemDao */
            $itemDao = MidasLoader::newDao('ItemDao');
            $itemDao->setName($name);
            $itemDao->setDescription('');
            $itemDao->setType(0);
            $itemDao->setPrivacyStatus(MIDAS_PRIVACY_PRIVATE); // Must set this flag private initially
            $itemModel->save($itemDao, false);
            $changes = 'Initial revision';

            if ($license === null) {
                $license = Zend_Registry::get('configGlobal')->defaultlicense;
            }

            $folderModel->addItem($parent, $itemDao);
            $itemModel->copyParentPolicies($itemDao, $parent);
            $itemPolicyUserModel->createPolicy($userDao, $itemDao, MIDAS_POLICY_ADMIN);
            $this->getLogger()->debug('Item uploaded ('.$itemDao->getName().', id='.$itemDao->getKey().')');
        }

        /** @var ItemRevisionModel $itemRevisionModel */
        $itemRevisionModel = MidasLoader::loadModel('ItemRevision');

        /** @var ItemRevisionDao $itemRevisionDao */
        $itemRevisionDao = MidasLoader::newDao('ItemRevisionDao');
        $itemRevisionDao->setChanges($changes);
        $itemRevisionDao->setUser_id($userDao->getKey());
        $itemRevisionDao->setDate(date('Y-m-d H:i:s'));
        $itemRevisionDao->setLicenseId($license);
        $itemModel->addRevision($itemDao, $itemRevisionDao);

        // Add bitstreams to the revision
        /** @var BitstreamDao $bitstreamDao */
        $bitstreamDao = MidasLoader::newDao('BitstreamDao');
        $bitstreamDao->setName($name);
        $bitstreamDao->setPath($path);

        if (empty($fileChecksum)) {
            $fileChecksum = UtilityComponent::md5file($path);
        }

        $bitstreamDao->setChecksum($fileChecksum);

        if (is_null($fileSize)) {
            $fileSize = UtilityComponent::fileSize($path);
        }

        $bitstreamDao->setSizebytes($fileSize);

        if (is_null($mimeType)) {
            /** @var MimeTypeComponent $mimeTypeComponent */
            $mimeTypeComponent = MidasLoader::loadComponent('MimeType');
            $mimeType = $mimeTypeComponent->getType($path, $name);
        }

        $bitstreamDao->setMimetype($mimeType);

        /** @var AssetstoreModel $assetStoreModel */
        $assetStoreModel = MidasLoader::loadModel('Assetstore');
        $assetStoreDao = $assetStoreModel->getDefault();

        if ($assetStoreDao === false) {
            throw new Zend_Exception('Unable to load default asset store');
        }

        $bitstreamDao->setAssetstoreId($assetStoreDao->getKey());

        // Upload the bitstream if necessary (based on the asset store type)
        $this->uploadBitstream($bitstreamDao, $assetStoreDao, $copy);
        $itemRevisionModel->addBitstream($itemRevisionDao, $bitstreamDao);

        Zend_Registry::get('notifier')->notifyEvent(
            'EVENT_CORE_UPLOAD_FILE',
            array($itemDao->toArray(), $itemRevisionDao->toArray())
        );
        Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_UPLOAD_FILE',
            array($itemDao->toArray(), $itemRevisionDao->toArray())
        );

        return $itemDao;
    }

    /**
     * Save new revision in the database
     *
     * @param UserDao $userDao The user who is creating the revision
     * @param string $name The name of the file being used to create the revision
     * @param string $path
     * @param string $changes The changes comment by the user
     * @param int $itemId The item to create the new revision in
     * @param null|int $itemRevisionNumber [optional][default=null] Revision number for the item
     * @param null|int $license [optional][default=null] License text for the revision
     * @param string $fileChecksum [optional][default=''] If passed, will use it instead of calculating it ourselves
     * @param bool $copy [optional][default=false] If true, will copy the file. Otherwise it will just move it into the assetstore.
     * @param null|int $fileSize If passed, will use it instead of calculating it ourselves
     * @param null|string $mimeType If passed, will use it instead of calculating it ourselves
     * @return ItemDao
     * @throws Zend_Exception
     */
    public function createNewRevision(
        $userDao,
        $name,
        $path,
        $changes,
        $itemId,
        $itemRevisionNumber = null,
        $license = null,
        $fileChecksum = '',
        $copy = false,
        $fileSize = null,
        $mimeType = null
    ) {
        if ($userDao === null) {
            throw new Zend_Exception('Please log in');
        }

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $itemDao = $itemModel->load($itemId);

        if ($itemDao === false) {
            throw new Zend_Exception('Unable to find item');
        }

        /** @var ItemRevisionModel $itemRevisionModel */
        $itemRevisionModel = MidasLoader::loadModel('ItemRevision');

        /** @var null|ItemRevisionDao $itemRevisionDao */
        $itemRevisionDao = null;

        if (isset($itemRevisionNumber)) {
            $revisions = $itemDao->getRevisions();
            foreach ($revisions as $revision) {
                if ($itemRevisionNumber == $revision->getRevision()) {
                    $itemRevisionDao = $revision;
                    break;
                }
            }
        }

        if (!$itemModel->policyCheck($itemDao, $userDao, MIDAS_POLICY_WRITE)) {
            throw new Zend_Exception('Parent permissions errors');
        }

        if ($itemRevisionDao === null) {
            /** @var ItemRevisionDao $itemRevisionDao */
            $itemRevisionDao = MidasLoader::newDao('ItemRevisionDao');
            $itemRevisionDao->setChanges($changes);
            $itemRevisionDao->setUser_id($userDao->getKey());
            $itemRevisionDao->setDate(date('Y-m-d H:i:s'));
            $itemRevisionDao->setLicenseId($license);
            $itemModel->addRevision($itemDao, $itemRevisionDao);
        } else {
            $itemRevisionDao->setChanges($changes);

            if ($license !== null) {
                $itemRevisionDao->setLicenseId($license);
            }

            $itemRevisionModel->save($itemRevisionDao);
        }

        /** @var BitstreamModel $bitstreamModel */
        $bitstreamModel = MidasLoader::loadModel('Bitstream');

        // Add bitstreams to the revision
        /** @var BitstreamDao $bitstreamDao */
        $bitstreamDao = MidasLoader::newDao('BitstreamDao');
        $bitstreamDao->setName($name);
        $bitstreamDao->setPath($path);

        if (empty($fileChecksum)) {
            $fileChecksum = UtilityComponent::md5file($path);
        }

        $bitstreamDao->setChecksum($fileChecksum);

        if (is_null($fileSize)) {
            $fileSize = UtilityComponent::fileSize($path);
        }

        $bitstreamDao->setSizebytes($fileSize);

        if (is_null($mimeType)) {
            /** @var MimeTypeComponent $mimeTypeComponent */
            $mimeTypeComponent = MidasLoader::loadComponent('MimeType');
            $mimeType = $mimeTypeComponent->getType($path, $name);
        }

        $bitstreamDao->setMimetype($mimeType);

        /** @var AssetstoreModel $assetStoreModel */
        $assetStoreModel = MidasLoader::loadModel('Assetstore');
        $assetStoreDao = $assetStoreModel->getDefault();

        if ($assetStoreDao === false) {
            throw new Zend_Exception('Unable to load default asset store');
        }

        $bitstreamDao->setAssetstoreId($assetStoreDao->getKey());

        // Upload the bitstream if necessary (based on the asset store type)
        $this->uploadBitstream($bitstreamDao, $assetStoreDao, $copy);
        $checksum = $bitstreamDao->getChecksum();
        $tmpBitstreamDao = $bitstreamModel->getByChecksum($checksum);
        if ($tmpBitstreamDao != false) {
            $bitstreamDao->setPath($tmpBitstreamDao->getPath());
            $bitstreamDao->setAssetstoreId($tmpBitstreamDao->getAssetstoreId());
        }
        $itemRevisionModel->addBitstream($itemRevisionDao, $bitstreamDao);
        // now that we have updated the itemRevision, the item may be stale
        $itemDao = $itemModel->load($itemId);

        $this->getLogger()->debug(
            'Revision uploaded: ['.$bitstreamDao->getName().'] into revision '.$itemRevisionDao->getKey(
            ).' (item '.$itemDao->getKey().')'
        );
        Zend_Registry::get('notifier')->notifyEvent(
            'EVENT_CORE_UPLOAD_FILE',
            array($itemRevisionDao->getItem()->toArray(), $itemRevisionDao->toArray())
        );
        Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_UPLOAD_FILE',
            array($itemRevisionDao->getItem()->toArray(), $itemRevisionDao->toArray())
        );

        return $itemDao;
    }
}
