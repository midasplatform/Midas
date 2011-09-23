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

/**
 * ExportComponentTest
 *
 * This class tests export component
 *
 * @category   Midas controller Test
 * @package    Midas Test
 */
class ExportComponentTest extends ControllerTestCase
  {

  /**
   * Helper function to recursively delete a directory
   *
   * @param type $directorypath Directory to be deleted
   * @return bool Success or not
   */
  private function _recursiveRemoveDirectory($directorypath)
    {
    // if the path has a slash at the end, remove it here
    $directorypath = rtrim($directorypath, '/');

    $handle = opendir($directorypath);
    if(!is_readable($directorypath))
      {
      return false;
      }
    while(false !== ($item = readdir($handle)))
      {
      if($item != '.' && $item != '..')
        {
        $path = $directorypath.'/'.$item;
        if(is_dir($path))
          {
          $this->_recursiveRemoveDirectory($path);
          }
        else
          {
          unlink($path);
          }
        }
      }
    closedir($handle);
    // try to delete the now empty directory
    if(!rmdir($directorypath))
      {
      return false;
      }
    return true;
    }

  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('User', 'Item', 'Bitstream');
    $this->_components = array('Export', 'Upload');
    parent::setUp();
    }

  /**
   * Helper function to upload items
   *
   * @param type $userDao User who will upload items
   */
  public function uploadItems($userDao)
    {
    // use UploadComponent
    require_once BASE_PATH.'/core/controllers/components/UploadComponent.php';
    $uploadCompoenent = new UploadComponent();
    // notifier is required in ItemRevisionModelBase::addBitstream, create a fake one
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
    // create a directory for testing the export component
    $midas_exporttest_dir = BASE_PATH.'/tmp/exportTest';
    if(file_exists($midas_exporttest_dir))
      {
      if(!$this->_recursiveRemoveDirectory($midas_exporttest_dir))
        {
        throw new Zend_Exception($midas_exporttest_dir." has already existed and we cannot delete it.");
        }
      }
    if(!mkdir($midas_exporttest_dir))
      {
      throw new Zend_Exception("Cannot create directory: ".$midas_exporttest_dir);
      }
    chmod($midas_exporttest_dir, 0777);

    // upload an item to user1's public folder
    $user1_public_path = $midas_exporttest_dir.'/user1_public.png';
    copy(BASE_PATH.'/tests/testfiles/search.png', $user1_public_path);
    $user1_public_fh = fopen($user1_public_path, "a+");
    fwrite($user1_public_fh, "content:user1_public");
    fclose($user1_public_fh);
    $user1_pulic_file_size = filesize($user1_public_path);
    $user1_public_filename = 'user1_public.png';
    $user1_public_parent = $userDao->getPublicFolder()->getKey();
    $license = 0;
    $uploadCompoenent->createUploadedItem($userDao, $user1_public_filename,
                                          $user1_public_path, $user1_public_parent, $license);

    // upload an item to user1's private folder
    $user1_private_path = $midas_exporttest_dir.'/user1_private.png';
    copy(BASE_PATH.'/tests/testfiles/search.png', $user1_private_path);
    $user1_private_fh = fopen($user1_private_path, "a+");
    fwrite($user1_private_fh, "content:user1_private");
    fclose($user1_private_fh);
    $user1_pulic_file_size = filesize($user1_private_path);
    $user1_private_filename = 'user1_private.png';
    $user1_private_parent = $userDao->getPrivateFolder()->getKey();
    $license = 0;
    $uploadCompoenent->createUploadedItem($userDao, $user1_private_filename,
                                          $user1_private_path, $user1_private_parent, $license);
    }

  /**
   * Helper function to get ItemIds as an input parameter
   * for ExportComponentTest::exportBitstreams
   *
   * @param UserDao $userDao
   * @param array $fileNames array of file names
   * @return array of itemIds
   */
  public function getItemIds($userDao, $fileNames)
    {
    $allItems = array();
    // get all the itemDaos
    foreach($fileNames as $file)
      {
      $items = $this->Item->getItemsFromSearch($file, $userDao);
      $allItems = array_merge($allItems, $items);
      }
    $itemIds = array();
     // process the items which pass the ITEM level policy check
    if(!empty($allItems))
      {
      foreach($allItems as $item)
        {
        $itemIds[] = $item->getKey();
        }
      }
    return $itemIds;
    }

  /**
   * Test ExportComponentTest::exportBitstreams function
   *
   * 1) user1 upload one file to his public folder, another file to his private folder
   * 2) export these two items as user1, both files should be exported.
   * 3) export these two items as user2, only the file in user1's public folder will
   *    be exported.
   */
  public function testCreateSymlinks()
    {
    $midas_exporttest_dir = BASE_PATH.'/tmp/exportTest';

    // user1 upload one file to his public folder, another file to his private folder
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $this->uploadItems($userDao);

    require_once BASE_PATH.'/core/controllers/components/ExportComponent.php';
    $exportCompoenent = new ExportComponent();
    $filenames = array();
    $filenames[] = "user1_public.png";
    $filenames[] = "user1_private.png";
    $itemIds = $this->getItemIds($userDao, $filenames);
    // symlinks should not exist before export
    $this->assertFalse(file_exists($midas_exporttest_dir.'/'.$itemIds[0].'/user1_public.png'));
    $this->assertFalse(file_exists($midas_exporttest_dir.'/'.$itemIds[1].'/user1_private.png'));
    // user1 export these two items
    $exportCompoenent->exportBitstreams($userDao, $midas_exporttest_dir, $itemIds, true);

    // user1's public file will be exported as a symlink file and the linked bitstream is also asserted
    $user1_public_item = $this->Item->load($itemIds[0]);
    $user1_public_revision = $this->Item->getLastRevision($user1_public_item);
    $user1_public_bitstreams = $user1_public_revision->getBitstreams();
    $user1_public_lastbitstream = end($user1_public_bitstreams);
    $user1_public_bitstream_path = $user1_public_lastbitstream->getAssetstore()->getPath().'/'.$user1_public_lastbitstream->getPath();
    $this->assertTrue(is_link($midas_exporttest_dir.'/'.$itemIds[0].'/user1_public.png'));
    $this->assertEquals($user1_public_bitstream_path, readlink($midas_exporttest_dir.'/'.$itemIds[0].'/user1_public.png'));
    // user1's private file will be exported as a symlink file and the linked bitstream is also asserted
    $user1_private_item = $this->Item->load($itemIds[1]);
    $user1_private_revision = $this->Item->getLastRevision($user1_private_item);
    $user1_private_bitstreams = $user1_private_revision->getBitstreams();
    $user1_private_lastbitstream = end($user1_private_bitstreams);
    $user1_private_bitstream_path = $user1_private_lastbitstream->getAssetstore()->getPath().'/'.$user1_private_lastbitstream->getPath();
    $this->assertTrue(is_link($midas_exporttest_dir.'/'.$itemIds[1].'/user1_private.png'));
    $this->assertEquals($user1_private_bitstream_path, readlink($midas_exporttest_dir.'/'.$itemIds[1].'/user1_private.png'));

    // switch to user2
    $userDao = $this->User->load($usersFile[1]->getKey());
    //user2 export the same two items as above
    $exportCompoenent->exportBitstreams($userDao, $midas_exporttest_dir, $itemIds, true);
    // user1's public file will be exported as a symlink file and the linked bitstream is also asserted
    $this->assertTrue(is_link($midas_exporttest_dir.'/'.$itemIds[0].'/user1_public.png'));
    $this->assertEquals($user1_public_bitstream_path, readlink($midas_exporttest_dir.'/'.$itemIds[0].'/user1_public.png'));
    // user1's private file will NOT be exported
    $this->assertFalse(file_exists($midas_exporttest_dir.'/'.$itemIds[1].'/user1_private.png'));
    } // end public function testCreateSymlinks

  } // end class
