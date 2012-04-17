<?php

/** Controller for downloading key files */
class Keyfiles_DownloadController extends Keyfiles_AppController
{
  var $_models = array('Bitstream', 'Item');

  /**
   * Download all key files for the head revision of an item
   */
  public function itemAction()
    {
    $itemId = $this->_getParam('itemId');
    if(!isset($itemId))
      {
      throw new Exception('Must pass an itemId parameter');
      }
    $item = $this->Item->load($itemId);
    if(!$item)
      {
      throw new Exception('Invalid itemId');
      }
    if(!$this->Item->policyCheck($item, $this->userSession->Dao))
      {
      throw new Exception('Read permission required');
      }
    $revision = $this->Item->getLastRevision($item);
    if(!$revision)
      {
      throw new Exception('Item must have at least one revision');
      }
    $this->disableView();
    $this->disableLayout();

    $this->_emptyOutputBuffer();
    ob_start(); //must start a new buffer for ZipStream to work

    Zend_Loader::loadClass('ZipStream', BASE_PATH.'/library/ZipStream/');
    $zip = new ZipStream($item->getName().'.zip');
    $bitstreams = $revision->getBitstreams();
    foreach($bitstreams as $bitstream)
      {
      $zip->add_file($bitstream->getName().'.md5', $bitstream->getChecksum());
      }
    $zip->finish();
    exit();
    }

  /**
   * Download the key file for a specific bitstream
   */
  public function bitstreamAction()
    {
    $bitstreamId = $this->_getParam('bitstreamId');
    if(!isset($bitstreamId))
      {
      throw new Exception('Must pass a bitstreamId parameter');
      }
    $bitstream = $this->Bitstream->load($bitstreamId);
    if(!$bitstream)
      {
      throw new Exception('Invalid bitstreamId');
      }
    $item = $bitstream->getItemrevision()->getItem();
    if(!$this->Item->policyCheck($item, $this->userSession->Dao))
      {
      throw new Exception('Read permission required');
      }
    $this->disableView();
    $this->disableLayout();

    $checksum = $bitstream->getChecksum();

    $this->_emptyOutputBuffer();

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$bitstream->getName().'.md5"');
    header('Content-Length: '.strlen($checksum));
    header('Expires: 0');
    header('Accept-Ranges: bytes');
    header('Cache-Control: private', false);
    header('Pragma: private');

    echo $checksum;
    exit();
    }

  /**
   * Kill the whole ob stack (Zend uses double nested output buffers)
   */
  private function _emptyOutputBuffer()
    {
    while(ob_get_level() > 0)
      {
      ob_end_clean();
      }
    }
}//end class