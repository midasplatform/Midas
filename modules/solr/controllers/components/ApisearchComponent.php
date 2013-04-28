<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

/** Component for api methods */
class Solr_ApisearchComponent extends AppComponent
{
  /**
   * Search using Lucene search text queries
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

    $componentLoader = new MIDAS_ComponentLoader();
    $solrComponent = $componentLoader->loadComponent('Solr', 'solr');
    $authComponent = $componentLoader->loadComponent('Authentication');
    $userDao = $authComponent->getUser($args,
                                       Zend_Registry::get('userSession')->Dao);

    $limit = array_key_exists('limit', $args) ? (int)$args['limit'] : 25;
    $itemIds = array();
    try
      {
      $index = $solrComponent->getSolrIndex();

      UtilityComponent::beginIgnoreWarnings(); //underlying library can generate warnings, we need to eat them
      $response = $index->search($args['query'], 0, $limit * 5, array('fl' => '*,score')); //extend limit to allow some room for policy filtering
      UtilityComponent::endIgnoreWarnings();

      $totalResults = $response->response->numFound;
      foreach($response->response->docs as $doc)
        {
        $itemIds[] = $doc->key;
        }
      }
    catch(Exception $e)
      {
      throw new Exception('Syntax error in query', -1);
      }

    $modelLoader = new MIDAS_ModelLoader();
    $itemModel = $modelLoader->loadModel('Item');
    $items = array();
    $count = 0;
    foreach($itemIds as $itemId)
      {
      $item = $itemModel->load($itemId);
      if($item && $itemModel->policyCheck($item, $userDao))
        {
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
        if(count($owningFolders) > 0)
          {
          $itemInfo['folder_id'] = $owningFolders[0]->getKey();
          }

        $revisionsArray = array();
        $revisions = $item->getRevisions();
        foreach($revisions as $revision)
          {
          if(!$revision)
            {
            continue;
            }
          $tmp = $revision->toArray();
          $revisionsArray[] = $tmp['itemrevision_id'];
         }
        $itemInfo['revisions'] = $revisionsArray;
        // get bitstreams only from last revision

        $headRevision = $itemModel->getLastRevision($item);
        $itemRevisionModel = MidasLoader::loadModel('ItemRevision');
        $metadata = $itemRevisionModel->getMetadata($headRevision);
        $metadataArray = array();
        foreach($metadata as $m)
          {
          $raw_met = $m->toArray();
          $metadataArray[$raw_met['element'] . '.' . $raw_met['qualifier']] = $raw_met['value'];
          }

        $bitstreamArray = array();
        $bitstreams = $headRevision->getBitstreams();
        foreach($bitstreams as $b)
          {
          $btmp = $b->toArray();
          $bitstreamArray[] = $btmp['bitstream_id'];
          }
        $itemInfo['bitstreams'] = $bitstreamArray;
        $itemInfo['metadata'] = $metadataArray;

        $items[] = $itemInfo;
        $count++;
        if($count >= $limit)
          {
          break;
          }
        }
      }
    return $items;
    }
} // end class
