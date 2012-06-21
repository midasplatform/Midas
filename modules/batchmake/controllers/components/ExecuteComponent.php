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
?>
<?php
/** Batchmake__ExecuteComponent */
class Batchmake_ExecuteComponent extends AppComponent
  {

  /**
   * takes a list of itemIds, expects these to each have a single bitstream,
   * exports these items to a work dir, and returns a list of
   * itemName => fullExportPath
   * @param type $userDao
   * @param type $taskDao
   * @param type $itemsForExport
   * @return string
   */
  public function exportSingleBitstreamItemsToWorkDataDir($userDao, $taskDao, $itemsForExport)
    {
    $itemIds = array();
    foreach($itemsForExport as $itemName => $itemId)
      {
      $itemIds[] = $itemId;
      }

    // export the items to the work dir data dir
    $datapath = $taskDao->getWorkDir() . '/' . 'data';
    if(!KWUtils::mkDir($datapath))
      {
      throw new Zend_Exception("couldn't create data export dir: ". $datapath);
      }
    $componentLoader = new MIDAS_ComponentLoader();
    $exportComponent = $componentLoader->loadComponent('Export');
    $symlink = true;
    $exportComponent->exportBitstreams($userDao, $datapath, $itemIds, $symlink);


    // for each of these items, generate a path that points to a single bitstream

    // get the bitstream path, assuming latest revision of item, with one bitstream
    // this seems somewhat wrong, as we are halfway recreating the export
    // and dependent upon the export to work in a certain way for this to work
    $modelLoad = new MIDAS_ModelLoader();
    $itemModel = $modelLoad->loadModel('Item');

    $itemNamesToBitstreamPaths = array();
    foreach($itemsForExport as $itemName => $itemId)
      {
      $itemDao = $itemModel->load($itemId);
      $revisionDao = $itemModel->getLastRevision($itemDao);
      $bitstreamDaos = $revisionDao->getBitstreams();
      if(empty($bitstreamDaos))
        {
        throw new Zend_Exception("Item ".$itemId." had no bitstreams.");
        }
      $imageBitstreamDao = $bitstreamDaos[0];
      $exportedBitstreamPath = $datapath . '/' . $itemId . '/' . $imageBitstreamDao->getName();
      $itemNamesToBitstreamPaths[$itemName] = $exportedBitstreamPath;
      }
    return $itemNamesToBitstreamPaths;
    }

  /**
   * exports a list of itemIds to a work dir.
   * @param type $userDao
   * @param type $taskDao
   * @param type $itemIds
   */
  public function exportItemsToWorkDataDir($userDao, $taskDao, $itemIds)
    {
    // export the items to the work dir data dir
    $datapath = $taskDao->getWorkDir() . '/' . 'data';
    if(!KWUtils::mkDir($datapath))
      {
      throw new Zend_Exception("couldn't create data export dir: ". $datapath);
      }
    $componentLoader = new MIDAS_ComponentLoader();
    $exportComponent = $componentLoader->loadComponent('Export');
    $symlink = true;
    $exportComponent->exportBitstreams($userDao, $datapath, $itemIds, $symlink);
    }


  /**
   * creates a python config file in a work dir,
   * with all of the information needed for the given user to communicate back
   * with this midas instance via the webapi, will be called config.cfg,
   * unless a prefix is supplied, then will be called prefixconfig.cfg.
   * @param type $taskDao
   * @param type $userDao
   * @param type $configPrefix
   */
  public function generatePythonConfigParams($taskDao, $userDao, $configPrefix = null)
    {
    // generate an config file for this run
    // HARDCODED
    $configs = array();
    // HACK how to get domain??
    $midasPath = Zend_Registry::get('webroot');
    $configs[] = 'url http://' . $_SERVER['HTTP_HOST'] . $midasPath;
    $configs[] = 'appname Default';

    $email = $userDao->getEmail();
    // get an api key for this user
    $modelLoad = new MIDAS_ModelLoader();
    $userApiModel = $modelLoad->loadModel('Userapi', 'api');
    $userApiDao = $userApiModel->getByAppAndUser('Default', $userDao);
    if(!$userApiDao)
      {
      throw new Zend_Exception('You need to create a web-api key for this user for application: Default');
      }
    $configs[] = 'email '.$email;
    $configs[] = 'apikey '.$userApiDao->getApikey();
    if($configPrefix !== null)
      {
      $filepath = $taskDao->getWorkDir() . '/' . $configPrefix . 'config.cfg';
      }
    else
      {
      $filepath = $taskDao->getWorkDir() . '/' . 'config.cfg';
      }

    if(!file_put_contents($filepath, implode("\n", $configs)))
      {
      throw new Zend_Exception('Unable to write configuration file: '.$filepath);
      }
    }

  /**
   * will generate a batchmake config file in the work dir.
   * @param type $taskDao
   * @param type $appTaskConfigProperties list of name=>value to be exported
   * @param type $condorPostScriptPath full path to the condor post script
   * @param type $condorDagPostScriptPath full path to condor dag post script
   * @param type $configScriptStem name of the batchmake script
   */
  public function generateBatchmakeConfig($taskDao, $appTaskConfigProperties, $condorPostScriptPath, $condorDagPostScriptPath, $configScriptStem)
    {
    $configFileLines = array();

    foreach($appTaskConfigProperties as $varName => $varValue)
      {
      if(is_array($varValue))
        {
        $configFileLine = "Set(" . $varName . " ";
        $values = array();
        foreach($varValue as $indVarValue)
          {
          $values[] = "'" . $indVarValue . "'";
          }
        $configFileLine .= implode(" ", $values);
        $configFileLine .= ")";
        $configFileLines[] = $configFileLine;
        }
      else
        {
        $configFileLines[] = "Set(" . $varName . " '" . $varValue . "')";
        }
      }
    $configFileLines[] = "Set(cfg_condorpostscript '" . $condorPostScriptPath . "')";
    $configFileLines[] = "Set(cfg_output_directory '" . $taskDao->getWorkDir() . "')";
    $configFileLines[] = "Set(cfg_exe '/usr/bin/python')";
    $configFileLines[] = "Set(cfg_condordagpostscript '" . $condorDagPostScriptPath . "')";
    $configFileLines[] = "Set(cfg_taskID '" . $taskDao->getBatchmakeTaskId() . "')";

    $configFilePath = $taskDao->getWorkDir() . "/" . $configScriptStem . ".config.bms";
    if(!file_put_contents($configFilePath, implode("\n", $configFileLines)))
      {
      throw new Zend_Exception('Unable to write configuration file: '.$configFilePath);
      }
    }








} // end class