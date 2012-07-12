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

/** Helper utilities for extracting archives into the Midas hierarchy */
class Archive_ExtractComponent extends AppComponent
  {
  /**
   * Extract an archive out of an item and into the hierarchy in place
   * @param itemDao The item representing the archive to extract
   * @param deleteArchive Whether to delete the archive item when finished extraction
   * @param progressDao (Optional) Dao for recording progress
   */
  public function extractInPlace($itemDao, $deleteArchive, $user, $progressDao = null)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $componentLoader = new MIDAS_ComponentLoader();
    $this->Item = $modelLoader->loadModel('Item');
    $this->Folder = $modelLoader->loadModel('Folder');
    $this->Folderpolicyuser = $modelLoader->loadModel('Folderpolicyuser');
    $this->Folderpolicygroup = $modelLoader->loadModel('Folderpolicygroup');
    $this->Progress = $modelLoader->loadModel('Progress');
    $this->UploadComponent = $componentLoader->loadComponent('Upload');
    $this->user = $user;

    $rev = $this->Item->getLastRevision($itemDao);
    if(!$rev)
      {
      throw new Zend_Exception('This item has no revisions');
      }
    $bitstreams = $rev->getBitstreams();
    if(count($bitstreams) !== 1)
      {
      throw new Zend_Exception('Head revision must have only one bitstream');
      }
    if($progressDao)
      {
      $this->Progress->updateProgress($progressDao, 0, 'Preparing to extract archive...');
      }
    $bitstreamDao = $bitstreams[0];
    $name = $bitstreamDao->getName();
    $folders = $itemDao->getFolders();
    $parentFolder = $folders[0];

    // First extract the archive into a temp location on disk
    if($this->_isFileExtension($name, '.zip'))
      {
      $extractedPath = $this->_extractZip($bitstreamDao, $parentFolder, $progressDao);
      }
    else
      {
      throw new Zend_Exception('This file is not a supported archive type');
      }

    // Next create the hierarchy from the temp location
    if($progressDao)
      {
      $this->Progress->updateProgress($progressDao, 0, 'Adding items to Midas tree...');
      }
    $this->_addToHierarchy($extractedPath, $parentFolder, $progressDao);

    // Clean up the dirs we made in the temp directory
    UtilityComponent::rrmdir($extractedPath);

    // Finally, delete existing archive item if user has specified to do so
    if($deleteArchive)
      {
      if($progressDao)
        {
        $progressDao->setMessage('Deleting old archive...');
        $this->Progress->save($progressDao);
        }
      $this->Item->delete($itemDao);
      }
    return $parentFolder;
    }

  /**
   * Extract a zip and returns the path where it was extracted.
   */
  protected function _extractZip($bitstreamDao, $parentFolder, $progressDao)
    {
    $dir = UtilityComponent::getTempDirectory().'/archive_'.$bitstreamDao->getKey().'_'.time();
    if(!mkdir($dir))
      {
      throw new Zend_Exception('Could not write into temp directory');
      }

    if($progressDao)
      {
      // If we want progress, let's read through the total entry count first
      $entryCount = 0;
      $zip = $this->_safeOpenZip($bitstreamDao->getFullPath());
      while($zip_entry = zip_read($zip))
        {
        $entryCount++;
        }
      zip_close($zip);

      $progressDao->setMaximum($entryCount);
      $this->Progress->save($progressDao);
      }

    $entryCount = 0;
    $zip = $this->_safeOpenZip($bitstreamDao->getFullPath());
    while($zipEntry = zip_read($zip))
      {
      $entryCount++;
      $entryName = zip_entry_name($zipEntry);
      if($progressDao)
        {
        $message = 'Extracting '.$progressDao->getCurrent().'/'.$progressDao->getMaximum().': '.$entryName;
        $this->Progress->updateProgress($progressDao, $entryCount, $message);
        }
      $this->_extractZipEntry($zip, $dir, $zipEntry, $entryName);
      }
    zip_close($zip);
    return $dir;
    }

  /**
   * Write a single zip entry to its appropriate location on disk
   */
  private function _extractZipEntry($zip, $baseDir, $zipEntry, $entryName)
    {
    $entryPath = $baseDir.'/'.$entryName;
    $isDir = substr($entryName, -1) == '/';
    if($isDir)
      {
      if(!is_dir($entryPath) && !mkdir($entryPath))
        {
        throw new Zend_Exception('Could not make directory for entry '.$entryPath);
        }
      }
    else
      {
      if(!zip_entry_open($zip, $zipEntry))
        {
        throw new Zend_Exception('Could not open zip entry '.$entryName);
        }
      $fh = fopen($entryPath, 'wb');
      while(($content = zip_entry_read($zipEntry, 204800)) != '') //200k chunk read
        {
        fwrite($fh, $content);
        }
      zip_entry_close($zipEntry);
      fclose($fh);
      }
    }

  /**
   * Recursive function for adding resources to the hierarchy from disk
   */
  protected function _addToHierarchy($path, $parentFolder, $progressDao)
    {
    $handle = opendir($path);
    while(false !== ($entry = readdir($handle)))
      {
      if($entry != '.' && $entry != '..')
        {
        $fullPath = $path.'/'.$entry;
        if(is_dir($fullPath))
          {
          if($progressDao)
            {
            $val = $progressDao->getCurrent() + 1;
            $message = $val.'/'.$progressDao->getMaximum().': Adding folder '.$entry;
            $this->Progress->updateProgress($progressDao, $val, $message);
            }
          $folder = $this->_addFolder($parentFolder, $fullPath);
          $this->_addToHierarchy($fullPath, $folder, $progressDao);
          }
        else
          {
          if($progressDao)
            {
            $val = $progressDao->getCurrent() + 1;
            $message = $val.'/'.$progressDao->getMaximum().': Adding item '.$entry;
            $this->Progress->updateProgress($progressDao, $val, $message);
            }
          $this->_addItem($parentFolder, $fullPath);
          }
        }
      }
    closedir($handle);
    }

  /**
   * Add the folder in the specified fullPath to the specified parentFolder
   */
  private function _addFolder($parentFolder, $fullPath)
    {
    $folder = $this->Folder->createFolder(basename($fullPath), '', $parentFolder);
    $policyGroup = $parentFolder->getFolderpolicygroup();
    $policyUser = $parentFolder->getFolderpolicyuser();
    foreach($policyGroup as $policy)
      {
      $group = $policy->getGroup();
      $policyValue = $policy->getPolicy();
      $this->Folderpolicygroup->createPolicy($group, $folder, $policyValue);
      }
    foreach($policyUser as $policy)
      {
      $user = $policy->getUser();
      $policyValue = $policy->getPolicy();
      $this->Folderpolicyuser->createPolicy($user, $folder, $policyValue);
      }
    return $folder;
    }

  /**
   * Add the item at the specified fullPath into the specified parentFolder
   */
  private function _addItem($parentFolder, $fullPath)
    {
    $name = basename($fullPath);
    $this->UploadComponent->createUploadedItem($this->user, $name, $fullPath, $parentFolder);
    }

  /**
   * Helper function to safely open the zip
   */
  private function _safeOpenZip($path)
    {
    $zip = zip_open($path);
    if(!is_resource($zip))
      {
      throw new Zend_Exception('Could not open the zip file');
      }
    return $zip;
    }

  /**
   * Return whether or not the filename has the given extension
   */
  private function _isFileExtension($filename, $ext)
    {
    return substr(strtolower($filename), -1 * strlen($ext)) === $ext;
    }
  } // end class
