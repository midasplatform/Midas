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

use google\appengine\api\cloud_storage\CloudStorageTools;

/** API upload component for the googleappengine module. */
class Googleappengine_ApiuploadComponent extends AppComponent
{
    /**
     * Generate an upload token and Google Cloud Storage upload URL.
     *
     * @param array $args
     * @return array
     */
    public function uploadToken($args)
    {
        /** @var ApisystemComponent $apisystemComponent */
        $apisystemComponent = MidasLoader::loadComponent('Apisystem');
        $data = $apisystemComponent->uploadGeneratetoken($args);
        $data['url'] = CloudStorageTools::createUploadUrl('/rest/googleappengine/upload/callback');

        return $data;
    }

    /**
     * Google Cloud Storage upload callback.
     *
     * @param array $args
     * @return array
     * @throws Exception
     */
    public function callback($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('uploadtoken'));

        if (!Zend_Controller_Front::getInstance()->getRequest()->isPost()) {
            throw new Exception('POST method required', MIDAS_HTTP_ERROR);
        }

        $uploadToken = $args['uploadtoken'];
        $path = UtilityComponent::getTempDirectory().'/'.$uploadToken;

        if (!file_exists($path)) {
            throw new Exception(
                'Invalid upload token '.$uploadToken, MIDAS_INVALID_UPLOAD_TOKEN
            );
        }

        @rmdir($path);
        list($userId, $itemId) = explode('/', $uploadToken);

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');
        $userDao = $userModel->load($userId);

        if ($userDao === false) {
            throw new Exception('Invalid user id from upload token', MIDAS_INVALID_PARAMETER);
        }

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $itemDao = $itemModel->load($itemId);

        if ($itemDao === false) {
            throw new Exception('Unable to find item', MIDAS_INVALID_PARAMETER);
        }

        if (!$itemModel->policyCheck($itemDao, $userDao, MIDAS_POLICY_WRITE)) {
            throw new Exception('Permission error', MIDAS_INVALID_POLICY);
        }

        if (array_key_exists('revision', $args)) {
            if (strtolower($args['revision']) == 'head') {
                $revision = $itemModel->getLastRevision($itemDao);
            } else {
                $revision = $itemModel->getRevision($itemDao, $args['revision']);
                if ($revision == false) {
                    throw new Exception('Unable to find revision', MIDAS_INVALID_PARAMETER);
                }
            }
        }

        if (!array_key_exists('file', $args) || !array_key_exists('file', $_FILES)
        ) {
            throw new Exception('Parameter file is not defined', MIDAS_INVALID_PARAMETER);
        }

        $file = $_FILES['file'];
        $name = $file['name'];
        $tmpName = $file['tmp_name'];
        $size = filesize($tmpName);

        $folderDaos = $itemDao->getFolders();

        /** @var FolderDao $folderDao */
        $folderDao = $folderDaos[0];

        $validations = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_VALIDATE_UPLOAD',
            array(
                'filename' => $name,
                'size' => $size,
                'path' => $tmpName,
                'folderId' => $folderDao->getFolderId(),
            )
        );

        foreach ($validations as $validation) {
            if (!$validation['status']) {
                throw new Exception($validation['message'], MIDAS_INVALID_POLICY);
            }
        }

        $license = isset($args['license']) ?  $args['license'] : null;
        $checksum = md5_file($tmpName);
        $revisionNumber = null;

        if (isset($revision) && $revision !== false) {
            $revisionNumber = $revision->getRevision();
        }

        if (isset($args['changes'])) {
            $changes = $args['changes'];
        } elseif ($revisionNumber === 1) {
            $changes = 'Initial revision';
        } else {
            $changes = '';
        }

        move_uploaded_file($tmpName, $path);

        /** @var UploadComponent $uploadComponent */
        $uploadComponent = MidasLoader::loadComponent('Upload');
        $itemDao = $uploadComponent->createNewRevision(
            $userDao,
            $name,
            $path,
            $changes,
            $itemDao->getKey(),
            $revisionNumber,
            $license,
            $checksum,
            false,
            $size
        );

        if ($itemDao === false) {
            throw new Exception('Upload failed', MIDAS_INTERNAL_ERROR);
        }

        return $itemDao->toArray();
    }
}
