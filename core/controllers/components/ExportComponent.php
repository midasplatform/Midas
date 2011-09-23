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
 * ExportComponent
 *
 *This class handles exporting bitstreams out of assetstores via creating symbolic links
 *
 * @category   Midas Core
 * @package    components
 */

class ExportComponent extends AppComponent
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
    // open the directory
    $handle = opendir($directorypath);

    if(!is_readable($directorypath))
      {
      return false;
      }
    // and scan through the items inside
    while(false !== ($item = readdir($handle)))
      {
      // if the filepointer is not the current directory or the parent directory
      if($item != '.' && $item != '..')
        {
        // build the new path to delete
        $path = $directorypath.'/'.$item;
        // if the new path is a directory
        if(is_dir($path))
          {
          // call this function with the new path
          $this->_recursiveRemoveDirectory($path);
          // if the new path is a file
          }
        else
          {
           // remove the file
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

  /**
   * Helper function to create a directory for an item
   *
   * @param string $directorypath   Directory to be created
   */
  private function _createItemDirectory($directorypath)
    {
    // if the directory exists, try to delete it first
    if(file_exists($directorypath))
      {
      if(!$this->_recursiveRemoveDirectory($directorypath))
        {
        throw new Zend_Exception($directorypath." has already existed and we cannot delete it.");
        }
      }
    if(!mkdir($directorypath))
      {
      throw new Zend_Exception("Cannot create directory: ".$directorypath);
      }
    chmod($directorypath, 0777);
    } // end _createItemDirectory

  /**
   * Export bistreams to target directory
   *
   * Given itemIds, do policy check on these itemIds,
   * then create symblic links to bitstreams (or copy the bitstreams)
   * in the "{targetDir}/{itemId}/" directories. If the {itemId} subdirectory
   * has been existed, delete the existing one first.
   * For policy check, we only check if the items are readable by the given user,
   * and don't furtherly distinguish among "can_read", "can_write", and "owner"
   * levels.
   *
   * @param UserDao $userDao
   * @param string $targetDir Target directory to export bitstreams
   * @param array $itemIds  Array of itemIds.
   *                        Each element is a comma seperatd value,
                            the 1st column is the actual item_id,
   *                        the 2nd is revision_number (optional)
   * @param bool $shouldSymLink Should we create symbolic links?
   *             If not, the bitstreams will be copied to the target directory
   */
  function exportBitstreams($userDao, $targetDir, $itemIds, $shouldSymLink)
    {
    // if the path has a slash at the end, remove it here
    $targetDir = rtrim($targetDir, '/');

    $modelLoad = new MIDAS_ModelLoader();
    $itemModel = $modelLoad->loadModel('Item');

    // Get items
    $revisions = array();
    if(!empty($itemIds))
      {
      foreach($itemIds as $itemId)
        {
        // $itemId is a comma seperatd value,
        // the 1st column is the actual item_id, the 2nd is revision_num (optional)
        $tmp = explode(',', $itemId);
        if(empty($tmp[0]))
          {
          continue;
          }
        // delete the itemId directory if it exists which means it was exported by
        // other user before
        $item_export_dir = $targetDir.'/'.$itemId;
        if(file_exists($item_export_dir))
          {
          if(!$this->_recursiveRemoveDirectory($item_export_dir))
            {
            throw new Zend_Exception($item_export_dir." has already existed and we cannot delete it.");
            }
          }
        $item = $itemModel->load($tmp[0]);
        // Only do policy check in the ITEM level now.
        // May also need to do policy check in the FOLDER level.
        if($item == false || !$itemModel->policyCheck($item, $userDao))
          {
          continue;
          }
        // Use the given revision_number if it is not empty
        if(isset($tmp[1]))
          {
          $tmp = $itemModel->getRevision($item, $tmp[1]);
          if($tmp !== false)
            {
            $revisions[] = $tmp;
            }
          }
        // Otherwise use the lastest revision
        else
          {
          $tmp = $itemModel->getLastRevision($item);
          if($tmp !== false)
            {
            $revisions[] = $tmp;
            }
          }
        } // end foreach($itemIds
      } // if(!empty($itemIds))

    // process the items which pass the ITEM level policy check
    if(!empty($revisions))
      {
      foreach($revisions as $revision)
        {
        $itemId =  $revision->getItemId();
        $this->_createItemDirectory($targetDir.'/'.$itemId);
        // itemRevision -> bitstream is a one-to-many relation (in bitstream table)
        $bitstreams = $revision->getBitstreams();
        if(!empty($bitstreams))
          {
          foreach($bitstreams as $bitstream)
            {
            // if the bitstream is not an actural file, such as url type, skip it
            if($bitstream->getChecksum() == ' ')
              {
              continue;
              }
            $source = $bitstream->getAssetstore()->getPath().'/'.$bitstream->getPath();
            $dest = $targetDir.'/'.$itemId.'/'.$bitstream->getName();
            // create symbolic links in target directory
            if($shouldSymLink)
              {
              // for symbolic link option,if mutliple bitstreams (in a single itemrevision)
              // have the same file name, add a '.new' suffix to distinguish them
              if(file_exists($dest))
                {
                $dest .= '.'.rand().'.new';
                }
              if(!symlink($source, $dest))
                {
                throw new Zend_Exception("Cannot create symlink: ".$dest."linked to".$source);
                }
              }
            // OR copy bitstreams to target directory
            else
              {
              // for copy option, if mutliple bitstreams (in a single itemrevision)
              // have the same file name, new file(s) wil overwrite the existing file(s)
              if(!copy($source, $dest))
                {
                throw new Zend_Exception("Cannot copy bitstream from: ".$source."to: ".$dest);
                }
              }
            } // end foreach($bitstreams as $bitstream)
          } // end if(!empty($bitstreams))
        } // end foreach ($revisions ...
      }
    } // end function exportBitstreams

} // end class ExportComponent
?>
