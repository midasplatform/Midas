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

require_once BASE_PATH.'/core/models/base/ItemRevisionModelBase.php';

/**
 * Pdo Model
 */
class ItemRevisionModel extends ItemRevisionModelBase
{
    /** get by uuid */
    public function getByUuid($uuid)
    {
        $row = $this->database->fetchRow($this->database->select()->where('uuid = ?', $uuid));
        $dao = $this->initDao(ucfirst($this->_name), $row);

        return $dao;
    }

    /** get the metadata associated with the revision */
    public function getMetadata($revisiondao)
    {
        if (!$revisiondao instanceof ItemRevisionDao) {
            throw new Zend_Exception("Error in param revisiondao when getting Metadata.");
        }

        $metadatavalues = array();
        $sql = $this->database->select()->setIntegrityCheck(false)->from('metadatavalue')->where(
            'itemrevision_id = ?',
            $revisiondao->getKey()
        )->joinLeft('metadata', 'metadata.metadata_id = metadatavalue.metadata_id');

        $rowset = $this->database->fetchAll($sql);
        foreach ($rowset as $row) {
            $metadata = $this->initDao('Metadata', $row);
            $metadatavalues[] = $metadata;
        }

        return $metadatavalues;
    }

    /**
     * delete the metadata associated with the revision, deleting all
     * metadata for that revision if no metadataId is passed.
     */
    public function deleteMetadata($revisiondao, $metadataId = null)
    {
        if (!$revisiondao instanceof ItemRevisionDao) {
            throw new Zend_Exception('Must pass a revision dao parameter');
        }

        $clause = 'itemrevision_id = '.$revisiondao->getKey();
        if ($metadataId !== null) {
            $clause .= ' AND metadata_id = '.$metadataId;
        }
        Zend_Registry::get('dbAdapter')->delete('metadatavalue', $clause);

        $item = $revisiondao->getItem();
        if (!$item) {
            return;
        }

        $itemModel = MidasLoader::loadModel('Item');
        $lastrevision = $itemModel->getLastRevision($item);

        // refresh lucene search index
        if ($lastrevision->getKey() == $revisiondao->getKey()) {
            $itemModel->save($item, true);
        }
    }

    /** Delete a revision.  Calling this will delete:
     * -The revision itself
     * -All bitstreams of the revision
     * -The feed for the creation of the revision
     * -The metadata associated with the revision
     */
    public function delete($revisiondao)
    {
        if (!$revisiondao instanceof ItemRevisionDao) {
            throw new Zend_Exception("Error in param revisiondao when deleting an ItemRevision.");
        }
        $bitstreams = $revisiondao->getBitstreams();
        $bitstream_model = MidasLoader::loadModel('Bitstream');
        foreach ($bitstreams as $bitstream) {
            $bitstream_model->delete($bitstream);
        }

        // explicitly typecast the id to a string, for postgres
        $deleteType = array(MIDAS_FEED_CREATE_REVISION);
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('p' => 'feed'))->where(
            'ressource = ?',
            (string)$revisiondao->getKey()
        );

        $rowset = $this->database->fetchAll($sql);

        $feed_model = MidasLoader::loadModel('Feed');
        foreach ($rowset as $row) {
            $feed = $this->initDao('Feed', $row);
            if (in_array($feed->getType(), $deleteType)) {
                $feed_model->delete($feed);
            }
        }

        // Delete metadata values for this revision
        $this->deleteMetadata($revisiondao);

        parent::delete($revisiondao);
        $revisiondao->saved = false;
        unset($revisiondao->itemrevision_id);
    }

    /** Returns the latest revision of a model */
    public function getLatestRevision($itemdao)
    {
        $row = $this->database->fetchRow(
            $this->database->select()->from($this->_name)->where(
                'item_id=?',
                $itemdao->getItemId()
            )->order('revision DESC')->limit(1)
        );

        return $this->initDao('ItemRevision', $row);
    }

    /** Returns the of the revision in Bytes */
    public function getSize($revision)
    {
        $row = $this->database->fetchRow(
            $this->database->select()->setIntegrityCheck(false)->from(
                'bitstream',
                array('sum(sizebytes) as sum')
            )->where('itemrevision_id=?', $revision->getKey())
        );

        return $row['sum'];
    }

    /** Return a bitstream by name */
    public function getBitstreamByName($revision, $name)
    {
        $row = $this->database->fetchRow(
            $this->database->select()->setIntegrityCheck(false)->from('bitstream')->where(
                'itemrevision_id=?',
                $revision->getItemrevisionId()
            )->where('name=?', $name)
        );

        return $this->initDao('Bitstream', $row);
    }

    /**
     * Used by the admin dashboard page. Counts the number of orphaned revision
     * records in the database.
     */
    public function countOrphans()
    {
        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('r' => 'itemrevision'),
            array('count' => 'count(*)')
        )->where(
            '(NOT r.item_id IN ('.new Zend_Db_Expr(
                $this->database->select()->setIntegrityCheck(false)->from(
                    array('subi' => 'item'),
                    array('item_id')
                ).'))'
            )
        );
        $row = $this->database->fetchRow($sql);

        return $row['count'];
    }

    /**
     * Call this to remove all orphaned item revision records
     */
    public function removeOrphans($progressDao = null)
    {
        if ($progressDao) {
            $max = $this->countOrphans();
            $progressDao->setMaximum($max);
            $progressDao->setMessage('Removing orphaned revisions (0/'.$max.')');
            $this->Progress = MidasLoader::loadModel('Progress');
            $this->Progress->save($progressDao);
        }

        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('r' => 'itemrevision'),
            array('itemrevision_id')
        )->where(
            '(NOT r.item_id IN ('.new Zend_Db_Expr(
                $this->database->select()->setIntegrityCheck(false)->from(
                    array('subi' => 'item'),
                    array('item_id')
                ).'))'
            )
        );
        $rowset = $this->database->fetchAll($sql);
        $ids = array();
        foreach ($rowset as $row) {
            $ids[] = $row['itemrevision_id'];
        }
        $itr = 0;
        foreach ($ids as $id) {
            if ($progressDao) {
                $itr++;
                $message = 'Removing orphaned revisions ('.$itr.'/'.$max.')';
                $this->Progress->updateProgress($progressDao, $itr, $message);
            }
            $revision = $this->load($id);
            if (!$revision) {
                continue;
            }
            $this->getLogger()->info(
                'Deleting orphaned revision '.$revision->getKey().' [item id='.$revision->getItemId().']'
            );
            $this->delete($revision);
        }
    }
}
