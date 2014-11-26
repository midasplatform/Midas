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

/** Component for api methods */
class Solr_ApisearchComponent extends AppComponent
{
    /**
     * Search using Lucene search text queries
     *
     * @path /solr/search
     * @http GET
     * @param query The Lucene search query
     * @param limit (Optional) The limit of the search; defaults to 25
     * @return The list of items matching the search query
     */
    public function searchAdvanced($args)
    {
        $apihelperComponent = MidasLoader::loadComponent('Apihelper');
        $apihelperComponent->validateParams($args, array('query'));

        $solrComponent = MidasLoader::loadComponent('Solr', 'solr');
        $authComponent = MidasLoader::loadComponent('Authentication');
        $userDao = $authComponent->getUser($args, Zend_Registry::get('userSession')->Dao);

        $limit = array_key_exists('limit', $args) ? (int) $args['limit'] : 25;
        $itemIds = array();
        try {
            $index = $solrComponent->getSolrIndex();

            UtilityComponent::beginIgnoreWarnings(); // underlying library can generate warnings, we need to eat them
            $response = $index->search(
                $args['query'],
                0,
                $limit * 5,
                array('fl' => '*,score')
            ); // extend limit to allow some room for policy filtering
            UtilityComponent::endIgnoreWarnings();

            foreach ($response->response->docs as $doc) {
                $itemIds[] = $doc->key;
            }
        } catch (Exception $e) {
            throw new Exception('Syntax error in query', -1);
        }

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $items = array();
        $count = 0;
        foreach ($itemIds as $itemId) {
            $item = $itemModel->load($itemId);
            if ($item && $itemModel->policyCheck($item, $userDao)) {
                $itemArray = $item->toArray();
                $itemInfo = array();
                $itemInfo['id'] = $itemArray['item_id'];
                $itemInfo['name'] = $itemArray['name'];
                $itemInfo['description'] = $itemArray['description'];
                $itemInfo['size'] = $itemArray['sizebytes'];
                $itemInfo['date_created'] = $itemArray['date_creation'];
                $itemInfo['date_updated'] = $itemArray['date_update'];
                $itemInfo['uuid'] = $itemArray['uuid'];
                $itemInfo['views'] = $itemArray['view'];
                $itemInfo['downloads'] = $itemArray['download'];
                $itemInfo['public'] = $itemArray['privacy_status'] == 0;
                $owningFolders = $item->getFolders();
                if (count($owningFolders) > 0) {
                    $itemInfo['folder_id'] = $owningFolders[0]->getKey();
                }

                $revisionsArray = array();
                $revisions = $item->getRevisions();
                foreach ($revisions as $revision) {
                    if (!$revision) {
                        continue;
                    }
                    $tmp = $revision->toArray();
                    $revisionsArray[] = $tmp['itemrevision_id'];
                }
                $itemInfo['revisions'] = $revisionsArray;
                // get bitstreams only from last revision
                $bitstreamArray = array();
                $headRevision = $itemModel->getLastRevision($item);
                $bitstreams = $headRevision->getBitstreams();
                foreach ($bitstreams as $b) {
                    $btmp = $b->toArray();
                    $bitstreamArray[] = $btmp['bitstream_id'];
                }
                $itemInfo['bitstreams'] = $bitstreamArray;

                $items[] = $itemInfo;
                $count++;
                if ($count >= $limit) {
                    break;
                }
            }
        }

        return $items;
    }
}
