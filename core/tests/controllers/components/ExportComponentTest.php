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

require_once BASE_PATH.'/library/KWUtils.php';

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
    $midas_exporttest_dir = $this->getTempDirectory().'/exportTest';
    if(file_exists($midas_exporttest_dir))
      {
      if(!KWUtils::recursiveRemoveDirectory($midas_exporttest_dir))
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
    $user1_public_path = $midas_exporttest_dir.'/public.file';
    copy(BASE_PATH.'/tests/testfiles/search.png', $user1_public_path);
    $user1_public_fh = fopen($user1_public_path, "a+");
    fwrite($user1_public_fh, "content:user1_public");
    fclose($user1_public_fh);
    $user1_pulic_file_size = filesize($user1_public_path);
    $user1_public_filename = 'public.file';
    $user1_public_parent = $userDao->getPublicFolder()->getKey();
    $license = 0;
    $uploadCompoenent->createUploadedItem($userDao, $user1_public_filename,
                                          $user1_public_path, $user1_public_parent, $license);

    // upload an item to user1's private folder
    $user1_private_path = $midas_exporttest_dir.'/private.png';
    copy(BASE_PATH.'/tests/testfiles/search.png', $user1_private_path);
    $user1_private_fh = fopen($user1_private_path, "a+");
    fwrite($user1_private_fh, "content:user1_private");
    fclose($user1_private_fh);
    $user1_pulic_file_size = filesize($user1_private_path);
    $user1_private_filename = 'private.png';
    $user1_private_parent = $userDao->getPrivateFolder()->getKey();
    $license = 0;
    $uploadCompoenent->createUploadedItem($userDao, $user1_private_filename,
                                          $user1_private_path, $user1_private_parent, $license);
    }

  /**
   * Helper function to get ItemIds as an input parameter
   * for ExportComponentTest::exportBitstreams
   *
   * @param array $fileNames array of file names
   * @return array of itemIds
   */
  public function getItemIds($fileNames)
    {
    // get all the itemDaos
    $allItems = array();
    $allItems = $this->Item->getAll();

    $itemIds = array();
    // process the items which pass the ITEM level policy check
    if(!empty($allItems))
      {
      foreach($fileNames as $file)
        {
        foreach($allItems as $item)
          {
          if($item->getName() == $file)
            {
            $itemIds[] = $item->getKey();
            }
          }
        }
      }
    return $itemIds;
    }

  /**
   * Test ExportComponentTest::exportBitstreams 'createSymlinks' functionality
   *
   * 1) user1 upload one file to his public folder, another file to his private folder
   * 2) export these two items as user1, both files should be exported.
   * 3) export these two items as user2, only the file in user1's public folder will
   *    be exported.
   */
  public function testCreateSymlinks()
    {
    $midas_exporttest_dir = $this->getTempDirectory().'/exportTest';
    // user1 upload one file to his public folder, another file to his private folder
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $this->uploadItems($userDao);

    require_once BASE_PATH.'/core/controllers/components/ExportComponent.php';
    $exportCompoenent = new ExportComponent();
    $filenames = array();
    $filenames[] = "public.file";
    $filenames[] = "private.png";
    $itemIds = $this->getItemIds($filenames);
    // symlinks should not exist before export
    $this->assertFalse(file_exists($midas_exporttest_dir.'/'.$itemIds[0].'/public.file'));
    $this->assertFalse(file_exists($midas_exporttest_dir.'/'.$itemIds[1].'/private.png'));
    // user1 export these two items
    $exportCompoenent->exportBitstreams($userDao, $midas_exporttest_dir, $itemIds, true);

    // user1's public file will be exported as a symlink file and the linked bitstream is also asserted
    $user1_public_item = $this->Item->load($itemIds[0]);
    $user1_public_revision = $this->Item->getLastRevision($user1_public_item);
    $user1_public_bitstreams = $user1_public_revision->getBitstreams();
    $user1_public_lastbitstream = end($user1_public_bitstreams);
    $user1_public_bitstream_path = $user1_public_lastbitstream->getAssetstore()->getPath().'/'.$user1_public_lastbitstream->getPath();
    $this->assertTrue(is_link($midas_exporttest_dir.'/'.$itemIds[0].'/public.file'));
    $this->assertEquals($user1_public_bitstream_path, readlink($midas_exporttest_dir.'/'.$itemIds[0].'/public.file'));

    // user1's private file will be exported as a symlink file and the linked bitstream is also asserted
    $user1_private_item = $this->Item->load($itemIds[1]);
    $user1_private_revision = $this->Item->getLastRevision($user1_private_item);
    $user1_private_bitstreams = $user1_private_revision->getBitstreams();
    $user1_private_lastbitstream = end($user1_private_bitstreams);
    $user1_private_bitstream_path = $user1_private_lastbitstream->getAssetstore()->getPath().'/'.$user1_private_lastbitstream->getPath();
    $this->assertTrue(is_link($midas_exporttest_dir.'/'.$itemIds[1].'/private.png'));
    $this->assertEquals($user1_private_bitstream_path, readlink($midas_exporttest_dir.'/'.$itemIds[1].'/private.png'));

    // switch to user2
    $userDao = $this->User->load($usersFile[1]->getKey());
    //user2 export the same two items as above
    $exportCompoenent->exportBitstreams($userDao, $midas_exporttest_dir, $itemIds, true);
    // user1's public file will be exported as a symlink file and the linked bitstream is also asserted
    $this->assertTrue(is_link($midas_exporttest_dir.'/'.$itemIds[0].'/public.file'));
    $this->assertEquals($user1_public_bitstream_path, readlink($midas_exporttest_dir.'/'.$itemIds[0].'/public.file'));
    // user1's private file will NOT be exported
    $this->assertFalse(file_exists($midas_exporttest_dir.'/'.$itemIds[1].'/private.png'));
    // clean up
    KWUtils::recursiveRemoveDirectory($midas_exporttest_dir);
    } // end public function testCreateSymlinks

  /**
   * Test ExportComponentTest::exportBitstreams 'copy' functiionality
   *
   * Because testCreateSymlinks function has covered most testing aspects,
   * this test only use a simple scenario
   * 1) user1 upload one file to his private folder
   * 2) export this item as user1, the file should be exported.
   */
  public function testCopy()
    {
    $midas_exporttest_dir = $this->getTempDirectory().'/exportTest';

    // user1 upload one file to his public folder, another file to his private folder
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $this->uploadItems($userDao);

    require_once BASE_PATH.'/core/controllers/components/ExportComponent.php';
    $exportCompoenent = new ExportComponent();
    $filenames = array();
    $filenames[] = "private.png";
    $itemIds = $this->getItemIds($filenames);
    // file should not exist before export
    $this->assertFalse(file_exists($midas_exporttest_dir.'/'.$itemIds[0].'/private.png'));
    // user1 export this item
    $exportCompoenent->exportBitstreams($userDao, $midas_exporttest_dir, $itemIds, false);

    // user1's private file will be exported (copied to the destination directory)
    $user1_private_item = $this->Item->load($itemIds[0]);
    $user1_private_revision = $this->Item->getLastRevision($user1_private_item);
    $user1_private_bitstreams = $user1_private_revision->getBitstreams();
    $user1_private_lastbitstream = end($user1_private_bitstreams);
    $user1_private_bitstream_path = $user1_private_lastbitstream->getAssetstore()->getPath().'/'.$user1_private_lastbitstream->getPath();
    $this->assertFileEquals($user1_private_bitstream_path, $midas_exporttest_dir.'/'.$itemIds[0].'/private.png');

    // clean up
    KWUtils::recursiveRemoveDirectory($midas_exporttest_dir);
    } // end public function testCopy

  /**
   * Test ExportComponentTest::exportBitstreams function using invalid input
   *
   * test case 1) input paremeter itemIds is not an array; expect an exception
   * test case 2) use valid item id with invalid revision number; expect an exception
   * test case 3) use invalid item id; expect an exception
   */
  public function testExportBitStreamsInvalidCases()
    {
    $midas_exporttest_dir = $this->getTempDirectory().'/exportTest';

    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $this->uploadItems($userDao);

    require_once BASE_PATH.'/core/controllers/components/ExportComponent.php';
    $exportCompoenent = new ExportComponent();
    $validFile = "public.file";
    $validItems = $this->Item->getItemsFromSearch($validFile, $userDao);
    $validItemId = $validItems[0]->getKey();
    $invalidRevision = 100;
    $invalidItemId = 1000;
    // test case 1)
    try
      {
      $exportCompoenent->exportBitstreams($userDao, $midas_exporttest_dir, $validItemId, true);
      $this->fail('Expected an exception exporting component, but didn not get one');
      }
    catch(Zend_Exception $ze)
      {
      // if we got here, this is the correct behavior
      $this->assertTrue(true);
      }
    // test case 2)
    $inputItemIds = array();
    $inputItemIds[] = $validItemId.','.$invalidRevision;
    try
      {
      $exportCompoenent->exportBitstreams($userDao, $midas_exporttest_dir, $inputItemIds, true);
      $this->fail('Expected an exception exporting component, but did not get one');
      }
    catch(Zend_Exception $ze)
      {
      // if we got here, this is the correct behavior
      $this->assertTrue(true);
      }
    //test case 3)
    $inputItemIds = array();
    $inputItemIds[] = $invalidItemId;
    try
      {
      $exportCompoenent->exportBitstreams($userDao, $midas_exporttest_dir, $inputItemIds, true);
      $this->fail('Expected an exception exporting component, but did not get one');
      }
    catch(Zend_Exception $ze)
      {
      // if we got here, this is the correct behavior
      $this->assertTrue(true);
      }

    // clean up
    KWUtils::recursiveRemoveDirectory($midas_exporttest_dir);
    }

  } // end class
