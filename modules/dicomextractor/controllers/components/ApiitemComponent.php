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

/** Apiitem Component for api methods */
class Dicomextractor_ApiitemComponent extends AppComponent
{
    /**
     * Extract the dicom metadata from a revision.
     *
     * @path /dicomextractor/item/{id}
     * @http PUT
     * @param id the id of the item to be extracted
     * @return the id of the revision
     *
     * @param array $args parameters
     * @throws Exception
     */
    public function extract($args)
    {
        /** @var ApihelperComponent $apihelperComponent */
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('id'));

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        /** @var AuthenticationComponent $authComponent */
        $authComponent = MidasLoader::loadComponent('Authentication');
        $itemDao = $itemModel->load($args['id']);
        $userDao = $authComponent->getUser($args, Zend_Registry::get('userSession')->Dao);
        if (!$itemModel->policyCheck($itemDao, $userDao, MIDAS_POLICY_WRITE)) {
            throw new Exception(
                'You didn\'t log in or you don\'t have the write '.'permission for the given item.',
                MIDAS_INVALID_POLICY
            );
        }

        $revisionDao = $itemModel->getLastRevision($itemDao);
        if ($revisionDao === false) {
            throw new Exception('The item has no revisions', MIDAS_INVALID_POLICY);
        }

        /** @var Dicomextractor_ExtractorComponent $dicomComponent */
        $dicomComponent = MidasLoader::loadComponent('Extractor', 'dicomextractor');
        $dicomComponent->extract($revisionDao);
        $dicomComponent->thumbnail($itemDao);

        return json_encode($revisionDao);
    }
}
