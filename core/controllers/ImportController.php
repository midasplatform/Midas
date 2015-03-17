<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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
 * Import Controller
 *
 * This controller exists to drive the local import, available from the
 * assetstore pane in the admin panel. This allows a Midas administrator
 * to pull in large amounts of data from a directory local to the server.
 *
 * There are a few architectural issues to note:
 *  1) This uses temporary files to mark progress in the local import.
 *  2) This assumes you do not want multiple copies of files an folders
 *     uploaded.
 */
class ImportController extends AppController
{
    public $_models = array(
        'Item',
        'Folder',
        'ItemRevision',
        'Assetstore',
        'Folderpolicyuser',
        'Itempolicyuser',
        'Itempolicygroup',
        'Group',
        'Folderpolicygroup',
    );
    public $_daos = array('Item', 'Folder', 'ItemRevision', 'Bitstream', 'Assetstore');
    public $_components = array('Upload', 'Utility');
    public $_forms = array('Import', 'Assetstore');

    // list of assetstores
    private $assetstores = array();

    // location of the progress file for ajax request (in the temp dir)
    private $progressfile;

    // location of the stop file for ajax request (in the temp dir)
    private $stopfile;

    // number of files to be processed
    private $ntotalfiles;

    // number of files that have been processed
    private $nfilesprocessed;

    // id of the assetstore to import the data
    private $assetstoreid;

    // should we create empty folder for empty directories
    private $importemptydirectories = true;

    /** Init Controller */
    public function init()
    {
        $this->view->activemenu = 'browse'; // set the active menu
    }

    /** Private function to count the number of files in the directories */
    private function _recursiveCountFiles($path)
    {
        $initialcount = 0;
        $it = new DirectoryIterator($path);
        foreach ($it as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            // file is not readable we skip
            if (!$fileInfo->isReadable()) {
                continue;
            }

            // we have a directory
            if ($fileInfo->isDir()) {
                $initialcount += $this->_recursiveCountFiles($fileInfo->getPathName());
            } else {
                $initialcount++;
            }
        }

        return $initialcount;
    }

    /** Check if the import should be stopped */
    private function _checkStopImport()
    {
        // maybe we should check for the content of the file but not necessary
        if (file_exists($this->stopfile)) {
            return true;
        }

        return false;
    }

    /** Increment the number of files processed and write the progress if needed */
    private function _incrementFileProcessed()
    {
        $this->nfilesprocessed++;
        $percent = ($this->nfilesprocessed / $this->ntotalfiles) * 100;
        $count = 2; // every 2%
        if ($percent % $count == 0) {
            file_put_contents($this->progressfile, $this->nfilesprocessed.'/'.$this->ntotalfiles);
        }
    }

    /** Import a directory recursively */
    private function _recursiveParseDirectory($path, $currentdir)
    {
        $it = new DirectoryIterator($path);
        foreach ($it as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            // If the file/dir is not readable (permission issue)
            if (!$fileInfo->isReadable()) {
                $this->getLogger()->crit($fileInfo->getPathName().' cannot be imported. Not readable.');
                continue;
            }

            // If this is too slow we'll figure something out
            if ($this->_checkStopImport()) {
                return false;
            }

            if ($fileInfo->isDir()) { // we have a directory

                // If the the directory actually doesn't exist at this point,
                // skip it.
                if (!file_exists($fileInfo->getPathName())) {
                    continue;
                }

                // Get the files in the directory and skip the folder if it does not
                // contain any files and we aren't set to import empty directories. The
                // count($files) <= 2 is there to account for our our friends . and ..
                $files = scandir($fileInfo->getPathName());
                if (!$this->importemptydirectories && $files && count($files) <= 2
                ) {
                    continue;
                }

                // Find if the child exists
                $child = $this->Folder->getFolderByName($currentdir, $fileInfo->getFilename());

                // If the folder does not exist, create one.
                if (!$child) {
                    $child = new FolderDao();
                    $child->setName($fileInfo->getFilename());
                    $child->setParentId($currentdir->getFolderId());
                    $child->setDateCreation(date('Y-m-d H:i:s'));
                    $child->setDescription('');
                    $this->Folder->save($child);
                    $this->Folderpolicyuser->createPolicy($this->userSession->Dao, $child, MIDAS_POLICY_ADMIN);
                }

                // Keep descending
                $this->_recursiveParseDirectory($fileInfo->getPathName(), $child);
            } else { // We have a file
                $this->_incrementFileProcessed();
                $newrevision = true;
                $item = $this->Folder->getItemByName($currentdir, $fileInfo->getFilename());
                if (!$item) {
                    // Create an item
                    $item = new ItemDao();
                    $item->setName($fileInfo->getFilename());
                    $item->setDescription('');
                    $item->setPrivacyStatus(MIDAS_PRIVACY_PRIVATE); // Must set this flag private initially
                    $this->Item->save($item, true);

                    // Set the policy of the item
                    $this->Itempolicyuser->createPolicy($this->userSession->Dao, $item, MIDAS_POLICY_ADMIN);

                    // Add the item to the current directory
                    $this->Folder->addItem($currentdir, $item);
                }

                // Check if the bistream has been updated based on the local date
                $revision = $this->ItemRevision->getLatestRevision($item);
                if ($revision) {
                    $newrevision = false;
                    $bitstream = $this->ItemRevision->getBitstreamByName($revision, $fileInfo->getFilename());
                    $curMD5 = UtilityComponent::md5file($fileInfo->getPathName());
                    $diskFileIsNewer = strtotime($bitstream->getDate()) < filemtime($fileInfo->getPathName());
                    $md5IsDifferent = $bitstream->getChecksum() != $curMD5;
                    if (!$bitstream || ($diskFileIsNewer && $md5IsDifferent)) {
                        $newrevision = true;
                    }
                }

                if ($newrevision) {
                    // Create a revision for the item
                    $itemRevisionDao = new ItemRevisionDao();
                    $itemRevisionDao->setChanges('Initial revision');
                    $itemRevisionDao->setUser_id($this->userSession->Dao->getUserId());
                    $this->Item->addRevision($item, $itemRevisionDao);

                    // Add bitstreams to the revision
                    $this->getLogger()->debug('create New Bitstream');
                    $bitstreamDao = new BitstreamDao();
                    $bitstreamDao->setName($fileInfo->getFilename());
                    $bitstreamDao->setPath($fileInfo->getPathName());
                    $bitstreamDao->fillPropertiesFromPath();

                    // Set the Assetstore
                    $bitstreamDao->setAssetstoreId($this->assetstoreid);

                    // Upload the bitstream
                    $assetstoreDao = $this->Assetstore->load($this->assetstoreid);
                    $this->Component->Upload->uploadBitstream($bitstreamDao, $assetstoreDao, true);

                    $this->ItemRevision->addBitstream($itemRevisionDao, $bitstreamDao);
                }
            }
        }

        unset($it);

        return true;
    }

    /**
     * Index Action (first action when we access the application)
     */
    public function indexAction()
    {
        $this->requireAdminPrivileges();

        // No time limit since import can take a long time
        UtilityComponent::setTimeLimit(0);
        $this->view->title = $this->t("Import");
        $this->view->header = $this->t("Import server-side data");

        $this->assetstores = $this->Assetstore->getAll();
        $this->view->assetstores = $this->assetstores;
        $this->view->importForm = $this->Form->Import->createImportForm($this->assetstores);
        $this->view->assetstoreForm = $this->Form->Assetstore->createAssetstoreForm();
    }

    /**
     * called from ajax
     */
    public function importAction()
    {
        $this->requireAdminPrivileges();

        // This is necessary in order to avoid session lock and being able to run two
        // ajax requests simultaneously
        session_write_close();

        $this->nfilesprocessed = 0;

        $this->disableLayout();
        $this->disableView();

        $this->assetstores = $this->Assetstore->getAll();
        $form = $this->Form->Import->createImportForm($this->assetstores);

        if ($this->getRequest()->isPost() && !$form->isValid($_POST)) {
            echo json_encode(array('error' => $this->t('The form is invalid. Missing values.')));

            return false;
        }

        // If we just validate the form we return
        if ($this->getRequest()->getPost('validate')) {
            echo json_encode(array('stage' => 'validate'));

            return false;
        } elseif ($this->getRequest()->getPost('initialize')) {
            // Count the total number of files in the directory and return it
            $this->ntotalfiles = $this->_recursiveCountFiles($form->inputdirectory->getValue());
            echo json_encode(array('stage' => 'initialize', 'totalfiles' => $this->ntotalfiles));

            return false;
        } elseif ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            $this->ntotalfiles = $this->getRequest()->getPost('totalfiles');

            // Parse the directory
            $pathName = $form->inputdirectory->getValue();
            $currentdirid = $form->importFolder->getValue(); // we just start with te initial dir
            $currentdir = new FolderDao();
            $currentdir->setFolderId($currentdirid);

            // Set the file locations used to handle the async requests
            $this->progressfile = $this->getTempDirectory()."/importprogress_".$form->uploadid->getValue();
            $this->stopfile = $this->getTempDirectory()."/importstop_".$form->uploadid->getValue();
            $this->assetstoreid = $form->assetstore->getValue();
            $this->importemptydirectories = $form->importemptydirectories->getValue();

            try {
                if ($this->_recursiveParseDirectory($pathName, $currentdir)) {
                    echo json_encode(array('message' => $this->t('Import successful.')));
                } else {
                    echo json_encode(
                        array(
                            'error' => $this->t(
                                'Problem occured while importing. '.'Check the log files or contact an '.'administrator.'
                            ),
                        )
                    );
                }
            } catch (Exception $e) {
                echo json_encode(array('error' => $this->t($e->getMessage())));
            }

            // Remove the temp and stop files
            UtilityComponent::safedelete($this->progressfile);
            UtilityComponent::safedelete($this->stopfile);

            return true;
        }

        echo json_encode(array('error' => $this->t('The request should be a post.')));

        return false;
    }

    /**
     * This function pulls the progress for the temporary importprogress_ file.
     */
    public function getprogressAction()
    {
        session_write_close();

        $this->disableLayout();
        $this->disableView();

        if (isset($this->_request->id)) {
            $progress['current'] = 0;
            $progress['max'] = 0;
            $progress['percent'] = 'NA';
            $file = $this->getTempDirectory()."/importprogress_".$this->_request->id;
            if (file_exists($file)) {
                $progressfile = explode('/', file_get_contents($file));
                $progress['current'] = $progressfile[0];
                $progress['max'] = $progressfile[1];
                $progress['percent'] = round($progress['current'] * 100 / $progress['max']);
            }
            echo json_encode($progress);

            return true;
        }

        return false;
    }

    /**
     * called from ajax
     */
    public function stopAction()
    {
        session_write_close();

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        if (isset($this->_request->id)) {
            $this->stopfile = $this->getTempDirectory()."/importstop_".$this->_request->id;
            file_put_contents($this->stopfile, $this->_request->id);

            return true;
        }

        return false;
    }
}
