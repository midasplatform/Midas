<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 20 rue de la Villette. 69328 Lyon, FRANCE
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

/** Index controller for the statistics module */
class Statistics_IndexController extends Statistics_AppController
{
  public $_moduleModels = array('Download');
  public $_models = array('Errorlog', 'Assetstore');
  public $_components = array('Utility');

  /** index action*/
  function indexAction()
    {
    if(!$this->logged || !$this->userSession->Dao->getAdmin() == 1)
      {
      throw new Zend_Exception("You should be an administrator");
      }

    $assetstores = $this->Assetstore->getAll();
    $defaultSet = false;
    foreach($assetstores as $key => $assetstore)
      {
      // Check if we can access the path
      if(file_exists($assetstore->getPath()))
        {
        $assetstores[$key]->totalSpace = disk_total_space($assetstore->getPath());
        $assetstores[$key]->usedSpace = disk_total_space($assetstore->getPath()) - disk_free_space($assetstore->getPath());
        $assetstores[$key]->freeSpace = disk_free_space($assetstore->getPath());
        $assetstores[$key]->usedSpaceText = round(($assetstores[$key]->usedSpace / $assetstores[$key]->totalSpace) * 100, 2);
        $assetstores[$key]->freeSpaceText = round((disk_free_space($assetstore->getPath()) / $assetstores[$key]->totalSpace) * 100, 2);
        }
      else
        {
        $assetstores[$key]->totalSpaceText = false;
        }
      }

    $jqplotAssetstoreArray = array();
    foreach($assetstores as $assetstore)
      {
      $jqplotAssetstoreArray[] =
        array($assetstore->getName().', '.$assetstore->getPath(),
          array(
            array('Free Space: '.$this->Component->Utility->formatSize($assetstore->freeSpace), $assetstore->freeSpaceText),
            array('Used Space: '.$this->Component->Utility->formatSize($assetstore->usedSpace), $assetstore->usedSpaceText)
               )
            );
      }

    $errors = $this->Errorlog->getLog(date('c', strtotime('-20 day'.date('Y-m-j G:i:s'))), date('c'), 'all', 2);
    $arrayErrors = array();

    $format = 'Y-m-j';
    for($i = 0; $i < 21; $i++)
      {
      $key = date($format, strtotime(date('c', strtotime('-'.$i.' day'.date('Y-m-j G:i:s')))));
      $arrayErrors[$key] = 0;
      }
    foreach($errors as $error)
      {
      $key = date($format, strtotime($error->getDatetime()));
      $arrayErrors[$key]++;
      }

    $jqplotArray = array();
    foreach($arrayErrors as $key => $value)
      {
      $jqplotArray[] = array($key.' 8:00AM', $value);
      }

    $this->view->json['stats']['errors'] = $jqplotArray;
    $this->view->json['stats']['assetstores'] = $jqplotAssetstoreArray;
    $modulesConfig = Zend_Registry::get('configsModules');
    $this->view->piwikUrl = $modulesConfig['statistics']->piwik->url;
    $this->view->piwikId = $modulesConfig['statistics']->piwik->id;
    $this->view->piwikKey = $modulesConfig['statistics']->piwik->apikey;
    }

}//end class
