<?php

/**
 * Upgrade 3.2.6 moves all of our item thumbnails into the default assetstore
 * as bitstreams.
 */
class Upgrade_3_2_6 extends MIDASUpgrade
{
  var $assetstore;

  public function preUpgrade()
    {
    set_time_limit(0);
    $modelLoader = new MIDAS_ModelLoader();
    $assetstoreModel = $modelLoader->loadModel('Assetstore');
    try
      {
      $this->assetstore = $assetstoreModel->getDefault();
      }
    catch(Exception $e)
      {
      }
    }

  public function mysql()
    {
    $this->db->query("ALTER TABLE `item` ADD COLUMN `thumbnail_id` bigint(20) NULL DEFAULT NULL");

    $this->_moveAllThumbnails();

    $this->db->query("ALTER TABLE `item` DROP `thumbnail`");
    }

  public function pgsql()
    {
    $this->db->query("ALTER TABLE item ADD COLUMN thumbnail_id bigint NULL DEFAULT NULL");

    $this->_moveAllThumbnails();

    $this->db->query("ALTER TABLE item DROP COLUMN thumbnail");
    }

  public function postUpgrade()
    {
    }

  private function _moveAllThumbnails()
    {
    // Iterate through all existing items that have thumbnails
    $sql = $this->db->select()
                ->from(array('item'))
                ->where('thumbnail != ?', '');
    $rowset = $this->db->fetchAll($sql);
    foreach($rowset as $row)
      {
      $itemId = $row['item_id'];
      $thumbnailBitstream = $this->_moveThumbnailToAssetstore($row['thumbnail']);
      if($thumbnailBitstream !== null)
        {
        $this->db->update('item',
                          array('thumbnail_id' => $thumbnailBitstream->getKey()),
                          array('item_id = ?' => $itemId));
        }
      }
    }

  private function _moveThumbnailToAssetstore($thumbnail)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $bitstreamModel = $modelLoader->loadModel('Bitstream');
    $bitstreamModel->loadDaoClass('BitstreamDao');

    $bitstreamDao = new BitstreamDao;

    $oldpath = BASE_PATH.'/'.$thumbnail;
    if(!file_exists($oldpath)) //thumbnail file no longer exists, so we remove its reference
      {
      return null;
      }
    $md5 = md5_file($oldpath);
    $bitstreamDao->setName('thumbnail.jpeg');
    $bitstreamDao->setItemrevisionId(-1); //-1 indicates this does not belong to any revision
    $bitstreamDao->setMimetype('image/jpeg');
    $bitstreamDao->setSizebytes(filesize($oldpath));
    $bitstreamDao->setChecksum($md5);

    $existing = $bitstreamModel->getByChecksum($md5);
    if($existing)
      {
      unlink($oldpath);
      $bitstreamDao->setPath($existing->getPath());
      $bitstreamDao->setAssetstoreId($existing->getAssetstoreId());
      }
    else
      {
      // Two-level hierarchy.
      $path = substr($md5, 0, 2).'/'.substr($md5, 2, 2).'/'.$md5;
      $fullpath = $this->assetstore->getPath().'/'.$path;

      //Create the directories
      $currentdir = $this->assetstore->getPath().'/'.substr($md5, 0, 2);
      $this->_createAssetstoreDirectory($currentdir);
      $currentdir .= '/'.substr($md5, 2, 2);
      $this->_createAssetstoreDirectory($currentdir);
      rename($oldpath, $fullpath);

      $bitstreamDao->setAssetstoreId($this->assetstore->getKey());
      $bitstreamDao->setPath($fullpath);
      }

    $bitstreamModel->save($bitstreamDao);
    return $bitstreamDao;
    }

  /** Helper function to create the two-level hierarchy in the assetstore */
  private function _createAssetstoreDirectory($directorypath)
    {
    if(!file_exists($directorypath))
      {
      if(!mkdir($directorypath))
        {
        throw new Zend_Exception("Cannot create directory: ".$directorypath);
        }
      chmod($directorypath, 0777);
      }
    }
}
?>
