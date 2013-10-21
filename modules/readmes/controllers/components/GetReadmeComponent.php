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

/** Exract readme text according to the folder or community*/
class Readmes_GetReadmeComponent extends AppComponent
{
  /**
   * Get the readme text from the specified folder
   */
  public function fromFolder($folder)
    {
    $folderModel = MidasLoader::loadModel('Folder');
    $itemModel = MidasLoader::loadModel('Item');
    $readmeItem = null;
    $candidates = array('readme.md', 'readme.txt', 'readme');
    foreach($candidates as $candidate)
      {
      $readmeItem = $folderModel->getItemByName($folder, $candidate, false);
      if($readmeItem != null)
        {
        break;
        }
      }

    if($readmeItem == null)
      {
      return array('text' => '');
      }
    $revisionDao = $itemModel->getLastRevision($readmeItem);
    $bitstreams = $revisionDao->getBitstreams();
    $bitstream = $bitstreams[0];
    $path = $bitstream->getAssetstore()->getPath().'/'.$bitstream->getPath();
    $contents = file_get_contents($path);
    return array('text' => $contents);
    }

  /**
   * Get the readme text from the specified community
   */
  public function fromCommunity($community)
    {
    if($community == null)
      {
      throw new Zend_Exception('Invalid Community');
      }
    $folderModel = MidasLoader::loadModel('Folder');
    $rootFolder = $community->getFolder();
    $publicFolder = $folderModel->getFolderByName($rootFolder, 'Public');
    return $this->fromFolder($publicFolder);
    }
}
