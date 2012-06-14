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

/** notification manager for solr module */
class Solr_Notification extends MIDAS_Notification
  {
  public $moduleName = 'solr';
  public $_models = array('Item', 'ItemRevision', 'Metadata');
  public $_moduleComponents = array('Solr');

  /** init notification process */
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_ITEM_SAVED', 'indexItem');
    $this->addCallBack('CALLBACK_CORE_ITEM_DELETED', 'itemDeleted');
    $this->addCallBack('CALLBACK_CORE_ITEM_SEARCH_DEFAULT_BEHAVIOR_OVERRIDE', 'itemSearch');

    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getAdminDashboard');

    $this->addTask('TASK_CORE_RESET_ITEM_INDEXES', 'resetItemIndexes', 'Recompute lucene indexes');
    }

  /** Add a tab to the manage community page for size quota */
  public function indexItem($args)
    {
    if(!$args['metadataChanged'])
      {
      return;
      }
    $item = $args['item'];
    try
      {
      $index = $this->ModuleComponent->Solr->getSolrIndex();
      }
    catch(Exception $e)
      {
      $this->getLogger()->warn($e->getMessage.' - Could not index item metadata ('.$item->getKey().')');
      return;
      }

    try
      {
      $response = $index->search('id: item_'.$item->getKey(), 0, 99999);
      foreach($response->response->docs as $doc)
        {
        $index->deleteById($doc->id);
        }
      if($response->response->numFound > 0)
        {
        $index->commit();
        }
      $doc = new Apache_Solr_Document();
      $doc->addField('id', 'item_'.$item->getKey());
      $doc->addField('key', $item->getKey());
      $doc->addField('name', $item->getName(), 3); //boost factor of 3 for name
      $doc->addField('description', $item->getDescription(), 2); //boost factor of 2 for description

      $revision = $this->Item->getLastRevision($item);

      if($revision != false)
        {
        $metadata = $this->ItemRevision->getMetadata($revision);
        $metadataString = '';

        foreach($metadata as $m)
          {
          $fieldName = $this->Metadata->mapTypeToName($m->getMetadatatype());
          $fieldName .= '-'.$m->getElement();
          if($m->getQualifier())
            {
            $fieldName .= '.'.$m->getQualifier();
            }
          $doc->addField($fieldName, $m->getValue());
          if(!is_numeric($m->getValue()))
            {
            $metadataString .= ' '.$m->getValue();
            }
          }
        $doc->addField('metadata', $metadataString);

        $bitstreams = $revision->getBitstreams();
        $md5String = '';
        foreach($bitstreams as $bitstream)
          {
          $md5String = $bitstream->getChecksum().' ';
          }
        $doc->addField('md5', trim($md5String));
        }
      $index->addDocument($doc);
      $index->commit();
      }
    catch(Exception $e)
      {
      $this->getLogger()->warn('Error saving item to Solr index: '.$e->getMessage());
      }
    }

  /**
   * Override the default item search behavior
   */
  public function itemSearch($args)
    {
    $query = $args['query'];
    $limit = $args['limit'];
    $user = $args['user'];

    $solrQuery = 'name: '.$query.
                 ' OR description: '.$query.
                 ' OR metadata: '.$query;

    $items = array('itemIds' => array());
    try
      {
      $index = $this->ModuleComponent->Solr->getSolrIndex();

      set_error_handler('Solr_Notification::eatWarnings'); //must not print and log warnings
      $response = $index->search($solrQuery, 0, ((int)$limit) * 3); //multiply limit by 3 to allow some room for policy filtering
      restore_error_handler(); //restore the existing error handler

      foreach($response->response->docs as $doc)
        {
        $items['itemIds'][] = $doc->key;
        }
      }
    catch(Exception $e)
      {
      // Probably shouldn't log this error, otherwise logs will get flooded from live search
      }
    return $items;
    }

  /**
   * When an item is deleted, we should remove its
   */
  public function itemDeleted($args)
    {
    $item = $args['item'];
    try
      {
      $index = $this->ModuleComponent->Solr->getSolrIndex();
      }
    catch(Exception $e)
      {
      $this->getLogger()->warn($e->getMessage.' - Could not delete item from index ('.$item->getKey().')');
      return;
      }

    try
      {
      $response = $index->search('id: item_'.$item->getKey(), 0, 99999);
      foreach($response->response->docs as $doc)
        {
        $index->deleteById($doc->id);
        }
      if($response->response->numFound > 0)
        {
        $index->commit();
        }
      }
    catch(Exception $e)
      {
      $this->getLogger()->warn('Error deleting item refs ('.$item->getKey().') from Solr index: '.$e->getMessage());
      }
    }

  /** Rebuild the solr lucene index */
  public function resetItemIndexes()
    {
    $this->ModuleComponent->Solr->rebuildIndex();
    echo "Done!";
    }

  /** Add a dashboard entry on the admin dashboard for showing solr status */
  public function getAdminDashboard()
    {
    try
      {
      $index = $this->ModuleComponent->Solr->getSolrIndex();
      $index->search('metadata: foo', 0, 1); //run a simple test query
      return array('Solr server accepting queries' => array(true));
      }
    catch(Exception $e)
      {
      return array('Solr server accepting queries' => array(false));
      }
    }

  /**
   * This is used to suppress warnings from being written to the output and the
   * error log.  When searching, we don't want warnings to appear for invalid searches.
   */
  static function eatWarnings($errno, $errstr, $errfile, $errline)
    {
    return true;
    }
  } //end class
?>
