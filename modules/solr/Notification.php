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
require_once BASE_PATH.'/modules/api/library/APIEnabledNotification.php';

/** notification manager for solr module */
class Solr_Notification extends ApiEnabled_Notification
  {
  public $moduleName = 'solr';
  public $_models = array('Item', 'ItemRevision', 'Metadata', 'Progress');
  public $_moduleComponents = array('Api', 'Solr');

  /** init notification process */
  public function init()
    {
    $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
    $this->moduleWebroot = $baseUrl.'/'.$this->moduleName;
    $this->webroot = $baseUrl;

    $this->addCallBack('CALLBACK_CORE_FOLDER_SAVED', 'indexFolder');
    $this->addCallBack('CALLBACK_CORE_FOLDER_DELETED', 'folderDeleted');
    $this->addCallBack('CALLBACK_CORE_FOLDER_SEARCH_DEFAULT_BEHAVIOR_OVERRIDE', 'folderSearch');

    $this->addCallBack('CALLBACK_CORE_ITEM_SAVED', 'indexItem');
    $this->addCallBack('CALLBACK_CORE_ITEM_DELETED', 'itemDeleted');
    $this->addCallBack('CALLBACK_CORE_ITEM_SEARCH_DEFAULT_BEHAVIOR_OVERRIDE', 'itemSearch');

    $this->addCallBack('CALLBACK_CORE_GET_DASHBOARD', 'getAdminDashboard');
    $this->addCallBack('CALLBACK_CORE_GET_LEFT_LINKS', 'getLeftLinks');

    $this->addTask('TASK_CORE_RESET_ITEM_INDEXES', 'resetItemFolderIndexes', 'Recompute lucene indexes');

    $this->enableWebAPI('solr');
    }

  /** Build the solr lucene index for items*/
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

    $progress = array_key_exists('progress', $args) ? $args['progress'] : null;
    if($progress)
      {
      $message = 'Indexing item '.($progress->getCurrent() + 1).' / '.$progress->getMaximum();
      $this->Progress->updateProgress($progress, $progress->getCurrent() + 1, $message);
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
      $doc->addField('name', $item->getName(), 3.0); //boost factor of 3 for name
      $doc->addField('description', $item->getDescription(), 2.0); //boost factor of 2 for description

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
      $this->getLogger()->warn('Error saving item ('.$item->getKey().') to Solr index: '.$e->getMessage());
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

    $items = array();
    try
      {
      $index = $this->ModuleComponent->Solr->getSolrIndex();

      UtilityComponent::beginIgnoreWarnings(); //must not print and log warnings
      $response = $index->search($solrQuery, 0, ((int)$limit) * 3, array('fl' => '*,score')); //multiply limit by 3 to allow some room for policy filtering
      UtilityComponent::endIgnoreWarnings();

      foreach($response->response->docs as $doc)
        {
        $items[] = array('id' => $doc->key, 'score' => $doc->score);
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
  public function resetItemFolderIndexes($params)
    {
    $progressDao = array_key_exists('progressDao', $params) ? $params['progressDao'] : null;
    $this->ModuleComponent->Solr->rebuildIndex($progressDao);
    echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Index rebuild complete'));
    }

  /** Build the solr lucene index for folders*/
  public function indexFolder($args)
    {
    $folder = $args['folder'];
    try
      {
      $index = $this->ModuleComponent->Solr->getSolrIndex();
      }
    catch(Exception $e)
      {
      $this->getLogger()->warn($e->getMessage.' - Could not index folder ('.$folder->getKey().')');
      return;
      }

    $progress = array_key_exists('progress', $args) ? $args['progress'] : null;
    if($progress)
      {
      $message = 'Indexing folder '.($progress->getCurrent() + 1).' / '.$progress->getMaximum();
      $this->Progress->updateProgress($progress, $progress->getCurrent() + 1, $message);
      }

    try
      {
      $response = $index->search('id: folder_'.$folder->getKey(), 0, 99999);
      foreach($response->response->docs as $doc)
        {
        $index->deleteById($doc->id);
        }
      if($response->response->numFound > 0)
        {
        $index->commit();
        }
      $doc = new Apache_Solr_Document();
      $doc->addField('id', 'folder_'.$folder->getKey());
      $doc->addField('key', $folder->getKey());
      $doc->addField('name', $folder->getName(), 3.0); //boost factor of 3 for name
      $doc->addField('description', $folder->getDescription(), 2.0); //boost factor of 2 for description
      $index->addDocument($doc);
      $index->commit();
      }
    catch(Exception $e)
      {
      $this->getLogger()->warn('Error saving folder ('.$folder->getKey().') to Solr index: '.$e->getMessage());
      }
    }


  /**
   * Override the default folder search behavior
   */
  public function folderSearch($args)
    {
    $query = $args['query'];
    $limit = $args['limit'];
    $user = $args['user'];

    $solrQuery = 'name: '.$query.
                 ' OR description: '.$query;

    $folders = array();
    try
      {
      $index = $this->ModuleComponent->Solr->getSolrIndex();

      UtilityComponent::beginIgnoreWarnings(); //must not print and log warnings
      $response = $index->search($solrQuery, 0, ((int)$limit) * 3, array('fl' => '*,score')); //multiply limit by 3 to allow some room for policy filtering
      UtilityComponent::endIgnoreWarnings();

      foreach($response->response->docs as $doc)
        {
        $folders[] = array('id' => $doc->key, 'score' => $doc->score);
        }
      }
    catch(Exception $e)
      {
      // Probably shouldn't log this error, otherwise logs will get flooded from live search
      }
    return $folders;
    }

  /**
   * When a folder is deleted, we should remove its
   */
  public function folderDeleted($args)
    {
    $folder = $args['folder'];
    try
      {
      $index = $this->ModuleComponent->Solr->getSolrIndex();
      }
    catch(Exception $e)
      {
      $this->getLogger()->warn($e->getMessage.' - Could not delete folder from index ('.$folder->getKey().')');
      return;
      }

    try
      {
      $response = $index->search('id: folder_'.$folder->getKey(), 0, 99999);
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
      $this->getLogger()->warn('Error deleting folder refs ('.$folder->getKey().') from Solr index: '.$e->getMessage());
      }
    }

  /** Add a dashboard entry on the admin dashboard for showing solr status */
  public function getAdminDashboard()
    {
    try
      {
      UtilityComponent::beginIgnoreWarnings();
      $index = $this->ModuleComponent->Solr->getSolrIndex();
      $index->search('metadata: foo', 0, 1); //run a simple test query
      UtilityComponent::endIgnoreWarnings();
      return array('Solr server accepting queries' => array(true));
      }
    catch(Exception $e)
      {
      UtilityComponent::endIgnoreWarnings();
      return array('Solr server accepting queries' => array(false));
      }
    }

  /** Add "Advanced Search" link to the left side links */
  public function getLeftLinks()
    {
    return array('Advanced search' => array(
      $this->webroot.'/solr/advanced',
      $this->webroot.'/core/public/images/icons/magnifier.png'));
    }
  } //end class
?>
