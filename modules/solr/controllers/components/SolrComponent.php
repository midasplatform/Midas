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

/** Component for accessing the solr server */
class Solr_SolrComponent extends AppComponent
{
  /**
   * Returns the Apache_Solr_Service object.
   */
  public function getSolrIndex()
    {
    $settingModel = MidasLoader::loadModel('Setting');
    $solrHost = $settingModel->getValueByName('solrHost', 'solr');
    $solrPort = $settingModel->getValueByName('solrPort', 'solr');
    $solrWebroot = $settingModel->getValueByName('solrWebroot', 'solr');

    if($solrHost === null)
      {
      throw new Zend_Exception('Solr settings not saved');
      }
    require_once BASE_PATH.'/modules/solr/library/Apache/Solr/Service.php';
    require_once BASE_PATH.'/modules/solr/library/Apache/Solr/Document.php';

    return new Apache_Solr_Service($solrHost, (int)$solrPort, $solrWebroot);
    }

  /**
   * Rebuilds the search index by iterating over all items and indexing each of them
   */
  public function rebuildIndex()
    {
    $itemModel = MidasLoader::loadModel('Item');
    $itemModel->iterateWithCallback('CALLBACK_CORE_ITEM_SAVED', 'item', array('metadataChanged' => true));

    $index = $this->getSolrIndex();
    $index->optimize();
    }
} // end class
