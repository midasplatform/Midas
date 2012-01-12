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

/**
 *  Import Controller
 *  Import Controller
 */
class ImportController extends AppController
  {
  public $_models = array('Item', 'Folder', 'ItemRevision', 'Assetstore', 'Folderpolicyuser', 'Itempolicyuser', 'Itempolicygroup', 'Group', 'Folderpolicygroup');
  public $_daos = array('Item', 'Folder', 'ItemRevision', 'Bitstream', 'Assetstore');
  public $_components = array('Upload', 'Utility');
  public $_forms = array('Import', 'Assetstore');

  private $assetstores = array(); // list of assetstores
  private $progressfile; // location of the progress file for ajax request (in the temp dir)
  private $stopfile; // location of the stop file for ajax request (in the temp dir)
  private $ntotalfiles; // number of files to be processed
  private $nfilesprocessed; // number of files that have been processed
  private $assetstoreid; // id of the assetstore to import the data
  private $importemptydirectories = true; // should we create empty folder for empty directories

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = 'browse'; // set the active menu
    }  // end init()

  /** Private function to count the number of files in the directories */
  private function _recursiveCountFiles($path)
    {
    $initialcount = 0;
    $it = new DirectoryIterator($path);
    foreach($it as $fileInfo)
      {
      if($fileInfo->isDot())
        {
        continue;
        }
      if(!$fileInfo->isReadable()) // file is not readable we skip
        {
        continue;
        }

      if($fileInfo->isDir()) // we have a directory
        {
        $initialcount += $this->_recursiveCountFiles($fileInfo->getPathName());
        }
      else
        {
        $initialcount++;
        }
      }
    return $initialcount;
    } // end function _recursiveCountFiles


  /** Check ifthe import should be stopped */
  private function _checkStopImport()
    {
    if(file_exists($this->stopfile)) // maybe we should check for the content of the file but not necessary
      {
      return true;
      }
    return false;
    } // end _checkStopImport

  /** Increment the number of files processed and write the progress ifneeded */
  private function _incrementFileProcessed()
    {
    $this->nfilesprocessed++;
    $percent = ($this->nfilesprocessed / $this->ntotalfiles) * 100;
    $count = 2; // every 2%
    if($percent % $count == 0)
      {
      file_put_contents($this->progressfile,
                        $this->nfilesprocessed.'/'.$this->ntotalfiles);
      }
    } // end _incrementFileProcessed

  /** Import a directory recursively */
  private function _recursiveParseDirectory($path, $currentdir)
    {
    set_time_limit(0); // No time limit since import can take a long time

    $it = new DirectoryIterator($path);
    foreach($it as $fileInfo)
      {
      if($fileInfo->isDot())
        {
        continue;
        }
      // If the file/dir is not readable (permission issue)
      if(!$fileInfo->isReadable())
        {
        $this->getLogger()->crit($fileInfo->getPathName(). ' cannot be imported. Not readable.');
        continue;
        }

      // If this is too slow we'll figure something out
      if($this->_checkStopImport())
        {
        return false;
        }

      if($fileInfo->isDir()) // we have a directory
        {
        if(!file_exists($fileInfo->getPathName()) || $fileInfo->getFilename() == 'CVS')
          {
          continue;
          }
        $files = scandir($fileInfo->getPathName());
        if(!$this->importemptydirectories
           && $files && count($files) <= 2)
          {
          continue;
          }

        // Find ifthe child exists
        $child = $this->Folder->getFolderByName($currentdir, $fileInfo->getFilename());
        if(!$child)
          {
          $child = new FolderDao;
          $child->setName($fileInfo->getFilename());
          $child->setParentId($currentdir->getFolderId());
          $child->setDateCreation(date('c'));
          $this->Folder->save($child);
          $this->Folderpolicyuser->createPolicy($this->userSession->Dao, $child, MIDAS_POLICY_ADMIN);
          $anonymousGroup = $this->Group->load(MIDAS_GROUP_ANONYMOUS_KEY);
          $this->Folderpolicygroup->createPolicy($anonymousGroup, $child, MIDAS_POLICY_READ);
          }
        $this->_recursiveParseDirectory($fileInfo->getPathName(), $child);
        }
      else // we have a file
        {
        $this->_incrementFileProcessed();
        $newrevision = true;
        $item = $this->Folder->getItemByName($currentdir, $fileInfo->getFilename());
        if(!$item)
          {
          // Create the item
          $item = new ItemDao;
          $item->setName($fileInfo->getFilename());
          $this->Item->save($item);

          // Set the policy of the item
          $this->Itempolicyuser->createPolicy($this->userSession->Dao, $item, MIDAS_POLICY_ADMIN);
          $anonymousGroup = $this->Group->load(MIDAS_GROUP_ANONYMOUS_KEY);
          $this->Itempolicygroup->createPolicy($anonymousGroup, $item, MIDAS_POLICY_READ);

          // Add the item to the current directory
          $this->Folder->addItem($currentdir, $item);

          } // end create item

        // Check ifthe bistream has been updated based on the local date
        $revision = $this->ItemRevision->getLatestRevision($item);
        if($revision)
          {
          $newrevision = false;
          $bitstream = $this->ItemRevision->getBitstreamByName($revision, $fileInfo->getFilename());
          if(!$bitstream // no bitstream yet
             ||
           (strtotime($bitstream->getDate()) < filemtime($fileInfo->getPathName()) // file on disk is newer
            && $bitstream->getChecksum() != UtilityComponent::md5file($fileInfo->getPathName()) // end md5 is different
            ))
            {
            $newrevision = true;
            }
          }

        if($newrevision)
          {
          // Create a revision for the item
          $itemRevisionDao = new ItemRevisionDao;
          $itemRevisionDao->setChanges('Initial revision');
          $itemRevisionDao->setUser_id($this->userSession->Dao->getUserId());
          $this->Item->addRevision($item, $itemRevisionDao);

          // Add bitstreams to the revision
          $bitstreamDao = new BitstreamDao;
          $bitstreamDao->setName($fileInfo->getFilename());
          $bitstreamDao->setPath($fileInfo->getPathName());
          $bitstreamDao->fillPropertiesFromPath();

          $bitstreamDao->setAssetstoreId($this->assetstoreid);

          // Upload the bitstream if necessary (based on the assetstore type)
          $assetstoreDao = $this->Assetstore->load($this->assetstoreid);

          $assetstorePath = $assetstoreDao->getPath();
          $bitstreamPath = $bitstreamDao->getPath();

          $bitstreamPath = substr($bitstreamPath, strlen($assetstorePath));
          $bitstreamDao->setPath($bitstreamPath);

          $this->Component->Upload->uploadBitstream($bitstreamDao, $assetstoreDao);

          $this->ItemRevision->addBitstream($itemRevisionDao, $bitstreamDao);
          } // end new revision

        } // end isFile
      } // end foreachfiles/dirs in the directory

    unset($it);
    return true;
    }  // end _recursiveParseDirectory

  /**
   * \fn indexAction()
   * \brief Index Action (first action when we access the application)
   */
  function indexAction()
    {
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return false;
      }

    set_time_limit(0); // No time limit since import can take a long time
    $this->view->title = $this->t("Import");
    $this->view->header = $this->t("Import server-side data");

    $this->assetstores = $this->Assetstore->getAll();
    $this->view->assetstores = $this->assetstores;
    $this->view->importForm = $this->Form->Import->createImportForm($this->assetstores);
    $this->view->assetstoreForm = $this->Form->Assetstore->createAssetstoreForm();
    }// end indexAction

  /**
   * \fn importAction()
   * \brief called from ajax
   */
  function importAction()
    {
    if(!$this->logged)
      {
      echo json_encode(array('error' => $this->t('User should be logged in')));
      return false;
      }

    // This is necessary in order to avoid session lock and being able to run two
    // ajax requests simultaneously
    session_write_close();

    $this->nfilesprocessed = 0;

    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $this->assetstores = $this->Assetstore->getAll();
    $form = $this->Form->Import->createImportForm($this->assetstores);

    if($this->getRequest()->isPost() && !$form->isValid($_POST))
      {
      echo json_encode(array('error' => $this->t('The form is invalid. Missing values.')));
      return false;
      }

    // If we just validate the form we return
    if($this->getRequest()->getPost('validate'))
      {
      echo json_encode(array('stage' => 'validate'));
      return false;
      }
    else if($this->getRequest()->getPost('initialize'))
      {
      // Count the total number of files in the directory and return it
      $this->ntotalfiles = $this->_recursiveCountFiles($form->inputdirectory->getValue());
      echo json_encode(array('stage' => 'initialize', 'totalfiles' => $this->ntotalfiles));
      return false;
      }
    else if($this->getRequest()->isPost() && $form->isValid($_POST))
      {
      $this->ntotalfiles = $this->getRequest()->getPost('totalfiles');

      // Parse the directory
      $pathName = $form->inputdirectory->getValue();
      $currentdirid = $form->importFolder->getValue(); // we just start with te initial dir
      $currentdir = new FolderDao;
      $currentdir->setFolderId($currentdirid);

      // Set the file locations used to handle the async requests
      $this->progressfile = $this->getTempDirectory()."/importprogress_".$form->uploadid->getValue();
      $this->stopfile = $this->getTempDirectory()."/importstop_".$form->uploadid->getValue();
      $this->assetstoreid = $form->assetstore->getValue();
      $this->importemptydirectories = $form->importemptydirectories->getValue();

      try
        {
        if($this->_recursiveParseDirectory($pathName, $currentdir))
          {
          echo json_encode(array('message' => $this->t('Import successful.')));
          }
        else
          {
          echo json_encode(array('error' => $this->t('Problem occured while importing. Check the log files or contact an administrator.')));
          }
        }
      catch(Exception $e)
        {
        echo json_encode(array('error' => $this->t($e->getMessage())));
        }


      // Remove the temp and stop files
      UtilityComponent::safedelete($this->progressfile);
      UtilityComponent::safedelete($this->stopfile);
      return true;
      }

    echo json_encode(array('error' => $this->t('The request should be a post.')));
    return false;
    } // end import action


  /**
   * \fn getprogressAction()
   * \brief called from ajax
   */
  function getprogressAction()
    {
    session_write_close();
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    if(isset($this->_request->id))
      {
      $progress['current'] = 0;
      $progress['max'] = 0;
      $progress['percent'] = 'NA';
      $file = $this->getTempDirectory()."/importprogress_".$this->_request->id;
      if(file_exists($file))
        {
        $progressfile = explode('/', file_get_contents($file));
        $progress['current'] = $progressfile[0];
        $progress['max'] = $progressfile[1];
        $progress['percent'] = round($progress['current'] * 100 / $progress['max']);
        }
      echo json_encode($progress);
      return true;
      }
    return false;
    } // end import action

  /**
   * \fn stopAction()
   * \brief called from ajax
   */
  function stopAction()
    {
    session_write_close();
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    if(isset($this->_request->id))
      {
      $this->stopfile = $this->getTempDirectory()."/importstop_".$this->_request->id;
      file_put_contents($this->stopfile, $this->_request->id);
      return true;
      }
    return false;
    } // end stopAction

} // end class

