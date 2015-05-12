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

/** These are the implementations of the web api methods for bitstream */
class ApibitstreamComponent extends AppComponent
{
    /**
     * Fetch the information about a bitstream.
     *
     * @path /bitstream/{id}
     * @http GET
     * @param id The id of the bitstream
     * @return Bitstream dao
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function bitstreamGet($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id'));

        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
        $userDao = $apihelperComponent->getUser($args);

        /** @var BitstreamModel $bitstreamModel */
        $bitstreamModel = MidasLoader::loadModel('Bitstream');
        $bitstream = $bitstreamModel->load($args['id']);

        if (!$bitstream) {
            throw new Exception('Invalid bitstream id', MIDAS_INVALID_PARAMETER);
        }

        if (array_key_exists('name', $args)) {
            $bitstream->setName($args['name']);
        }

        /** @var ItemRevisionModel $revisionModel */
        $revisionModel = MidasLoader::loadModel('ItemRevision');
        $revision = $revisionModel->load($bitstream->getItemrevisionId());

        if (!$revision) {
            throw new Exception('Invalid revision id', MIDAS_INTERNAL_ERROR);
        }

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $item = $itemModel->load($revision->getItemId());
        if (!$item || !$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ)
        ) {
            throw new Exception("This item doesn't exist or you don't have the permissions.", MIDAS_INVALID_POLICY);
        }
        $bitstreamArray = array();
        $bitstreamArray['id'] = $bitstream->getBitstreamId();
        $bitstreamArray['name'] = $bitstream->getName();
        $bitstreamArray['size'] = $bitstream->getSizebytes();
        $bitstreamArray['mimetype'] = $bitstream->getMimetype();
        $bitstreamArray['checksum'] = $bitstream->getChecksum();
        $bitstreamArray['itemrevision_id'] = $bitstream->getItemrevisionId();
        $bitstreamArray['item_id'] = $revision->getItemId();
        $bitstreamArray['date'] = $bitstream->getDate();

        return $bitstreamArray;
    }

    /**
     * Wrapper function to make our bitstream get sane.
     *
     * @param array $args
     * @return array
     */
    public function bitstreamGetWrapper($args)
    {
        $in = $this->bitstreamGet($args);
        $out = array();
        $out['id'] = $in['id'];
        $out['item_id'] = $in['item_id'];
        $out['itemrevision_id'] = $in['itemrevision_id'];
        $out['name'] = $in['name'];
        $out['mimetype'] = $in['mimetype'];
        $out['size'] = $in['size'];
        $out['md5'] = $in['checksum'];
        $out['date_created'] = $in['date'];
        $out['date_updated'] = $in['date']; // Fix this later

        return $out;
    }

    /**
     * Change the properties of a bitstream. Requires write access to the containing item.
     *
     * @path /bitstream/{id}
     * @http PUT
     * @param id The id of the bitstream to edit
     * @param name (Optional) New name for the bitstream
     * @param mimetype (Optional) New MIME type for the bitstream
     * @return The bitstream dao
     *
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function bitstreamEdit($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_WRITE_DATA));
        $userDao = $apihelperComponent->getUser($args);

        /** @var BitstreamModel $bitstreamModel */
        $bitstreamModel = MidasLoader::loadModel('Bitstream');
        $bitstream = $bitstreamModel->load($args['id']);

        if (!$bitstream) {
            throw new Exception('Invalid bitstream id', MIDAS_INVALID_PARAMETER);
        }

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        if (!$itemModel->policyCheck($bitstream->getItemrevision()->getItem(), $userDao, MIDAS_POLICY_WRITE)
        ) {
            throw new Exception('Write access on item is required', MIDAS_INVALID_POLICY);
        }

        if (array_key_exists('name', $args)) {
            $bitstream->setName($args['name']);
        }
        if (array_key_exists('mimetype', $args)) {
            $bitstream->setMimetype($args['mimetype']);
        }
        $bitstreamModel->save($bitstream);

        return $bitstream->toArray();
    }

    /**
     * Delete a bitstream. Requires admin privileges on the containing item.
     *
     * @path /bitstream/{id}
     * @http DELETE
     * @param id The id of the bitstream to delete
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function bitstreamDelete($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_ADMIN_DATA));
        $userDao = $apihelperComponent->getUser($args);

        /** @var BitstreamModel $bitstreamModel */
        $bitstreamModel = MidasLoader::loadModel('Bitstream');
        $bitstream = $bitstreamModel->load($args['id']);

        if (!$bitstream) {
            throw new Exception('Invalid bitstream id', MIDAS_INVALID_PARAMETER);
        }

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        if (!$itemModel->policyCheck($bitstream->getItemrevision()->getItem(), $userDao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Exception('Admin privileges required on the containing item', MIDAS_INVALID_POLICY);
        }

        $bitstreamModel->delete($bitstream);
    }

    /**
     * Count the bitstreams under a containing resource. Uses latest revision of each item.
     *
     * @path /bitstream/count
     * @http GET
     * @param uuid The uuid of the containing resource
     * @return array(size=>total_size_in_bytes, count=>total_number_of_files)
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function bitstreamCount($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('uuid'));
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
        $userDao = $apihelperComponent->getUser($args);

        /** @var UuidComponent $uuidComponent */
        $uuidComponent = MidasLoader::loadComponent('Uuid');
        $resource = $uuidComponent->getByUid($args['uuid']);

        if ($resource == false) {
            throw new Exception('No resource for the given UUID.', MIDAS_INVALID_PARAMETER);
        }

        switch ($resource->resourceType) {
            case MIDAS_RESOURCE_COMMUNITY:
                /** @var CommunityModel $communityModel */
                $communityModel = MidasLoader::loadModel('Community');
                if (!$communityModel->policyCheck($resource, $userDao, MIDAS_POLICY_READ)
                ) {
                    throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
                }

                return $communityModel->countBitstreams($resource, $userDao);
            case MIDAS_RESOURCE_FOLDER:
                /** @var FolderModel $folderModel */
                $folderModel = MidasLoader::loadModel('Folder');
                if (!$folderModel->policyCheck($resource, $userDao, MIDAS_POLICY_READ)
                ) {
                    throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
                }

                return $folderModel->countBitstreams($resource, $userDao);
            case MIDAS_RESOURCE_ITEM:
                /** @var ItemModel $itemModel */
                $itemModel = MidasLoader::loadModel('Item');
                if (!$itemModel->policyCheck($resource, $userDao, MIDAS_POLICY_READ)
                ) {
                    throw new Exception('Invalid policy', MIDAS_INVALID_POLICY);
                }

                return $itemModel->countBitstreams($resource);
            default:
                throw new Exception('Invalid resource type', MIDAS_INTERNAL_ERROR);
        }
    }

    /**
     * Download a bitstream either by its id or by a checksum.
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function bitstreamDownload($args)
    {
        if (!array_key_exists('id', $args) && !array_key_exists('checksum', $args)
        ) {
            throw new Exception('Either an id or checksum parameter is required', MIDAS_INVALID_PARAMETER);
        }
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->requirePolicyScopes(array(MIDAS_API_PERMISSION_SCOPE_READ_DATA));
        $userDao = $apihelperComponent->getUser($args);

        /** @var BitstreamModel $bitstreamModel */
        $bitstreamModel = MidasLoader::loadModel('Bitstream');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        if (array_key_exists('id', $args)) {
            $bitstream = $bitstreamModel->load($args['id']);
        } else {
            $bitstreams = $bitstreamModel->getByChecksum($args['checksum'], true);
            $bitstream = null;
            foreach ($bitstreams as $candidate) {
                $rev = $candidate->getItemrevision();
                if (!$rev) {
                    continue;
                }
                $item = $rev->getItem();
                if ($itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ)
                ) {
                    $bitstream = $candidate;
                    break;
                }
            }
        }

        if (!$bitstream) {
            throw new Exception(
                'The bitstream does not exist or you do not have the permissions',
                MIDAS_INVALID_PARAMETER
            );
        }

        $revision = $bitstream->getItemrevision();
        if (!$revision) {
            throw new Exception('Bitstream does not belong to a revision', MIDAS_INTERNAL_ERROR);
        }
        $item = $revision->getItem();
        if (!$itemModel->policyCheck($item, $userDao, MIDAS_POLICY_READ)) {
            throw new Exception('Read permission required', MIDAS_INVALID_POLICY);
        }

        $name = array_key_exists('name', $args) ? $args['name'] : $bitstream->getName();
        $offset = array_key_exists('offset', $args) ? $args['offset'] : '0';

        $redirUrl = '/download/?bitstream='.$bitstream->getKey().'&offset='.$offset.'&name='.$name;
        if ($userDao && array_key_exists('token', $args)) {
            $redirUrl .= '&authToken='.$args['token'];
        }
        $r = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $r->gotoUrl($redirUrl);
    }

    /**
     * Download a bitstream by its id.
     *
     * @path /bitstream/download/{id}
     * @http GET
     * @param id The id of the bitstream
     * @param name (Optional) Alternate filename to download as
     * @param offset (Optional) The download offset in bytes (used for resume)
     *
     * @param array $args parameters
     */
    public function bitstreamDownloadById($args)
    {
        $this->bitstreamDownload($args);
    }

    /**
     * Download a bitstream by a checksum.
     *
     * @path /bitstream/download
     * @http GET
     * @param checksum The checksum of the bitstream
     * @param name (Optional) Alternate filename to download as
     * @param offset (Optional) The download offset in bytes (used for resume)
     *
     * @param array $args parameters
     */
    public function bitstreamDownloadByChecksum($args)
    {
        $this->bitstreamDownload($args);
    }
}
