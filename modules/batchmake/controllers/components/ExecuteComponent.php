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

/** Batchmake__ExecuteComponent */
class Batchmake_ExecuteComponent extends AppComponent
{
    /**
     * takes a list of itemNames => itemIds, expects these to each have a single bitstream,
     * exports these items to a work dir, and returns a list of
     * itemName => fullExportPath
     *
     * @param UserDao $userDao
     * @param Batchmake_TaskDao $taskDao
     * @param array $itemsForExport array itemNames => itemIds
     * @return array
     * @throws Zend_Exception
     */
    public function exportSingleBitstreamItemsToWorkDataDir($userDao, $taskDao, $itemsForExport)
    {
        $itemIds = array();
        foreach ($itemsForExport as $itemId) {
            $itemIds[] = $itemId;
        }

        // export the items to the work dir data dir
        $datapath = $taskDao->getWorkDir().'/'.'data';
        if (!KWUtils::mkDir($datapath)) {
            throw new Zend_Exception("couldn't create data export dir: ".$datapath);
        }

        /** @var ExportComponent $exportComponent */
        $exportComponent = MidasLoader::loadComponent('Export');
        $symlink = true;
        $exportComponent->exportBitstreams($userDao, $datapath, $itemIds, $symlink);

        // for each of these items, generate a path that points to a single bitstream

        // get the bitstream path, assuming latest revision of item, with one bitstream
        // this seems somewhat wrong, as we are halfway recreating the export
        // and dependent upon the export to work in a certain way for this to work
        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        $itemNamesToBitstreamPaths = array();
        foreach ($itemsForExport as $itemName => $itemId) {
            $itemDao = $itemModel->load($itemId);
            $revisionDao = $itemModel->getLastRevision($itemDao);
            if ($revisionDao === false) {
                throw new Zend_Exception('The item has no revisions', MIDAS_INVALID_POLICY);
            }
            $bitstreamDaos = $revisionDao->getBitstreams();
            if (empty($bitstreamDaos)) {
                throw new Zend_Exception("Item ".$itemId." has no bitstreams.");
            }
            $imageBitstreamDao = $bitstreamDaos[0];
            $exportedBitstreamPath = $datapath.'/'.$itemId.'/'.$imageBitstreamDao->getName();
            $itemNamesToBitstreamPaths[$itemName] = $exportedBitstreamPath;
        }

        return $itemNamesToBitstreamPaths;
    }

    /**
     * exports a list of itemIds to a work dir.
     *
     * @param UserDao $userDao
     * @param Batchmake_TaskDao $taskDao
     * @param array $itemIds
     * @throws Zend_Exception
     */
    public function exportItemsToWorkDataDir($userDao, $taskDao, $itemIds)
    {
        // export the items to the work dir data dir
        $datapath = $taskDao->getWorkDir().'/'.'data';
        if (!KWUtils::mkDir($datapath)) {
            throw new Zend_Exception("couldn't create data export dir: ".$datapath);
        }

        /** @var ExportComponent $exportComponent */
        $exportComponent = MidasLoader::loadComponent('Export');
        $symlink = true;
        $exportComponent->exportBitstreams($userDao, $datapath, $itemIds, $symlink);
    }

    /**
     * creates a python config file in a work dir,
     * with all of the information needed for the given user to communicate back
     * with this midas instance via the web API, will be called config.cfg,
     * unless a prefix is supplied, then will be called prefixconfig.cfg.
     *
     * @param Batchmake_TaskDao $taskDao
     * @param UserDao $userDao
     * @param null|string $configPrefix
     * @throws Zend_Exception
     */
    public function generatePythonConfigParams($taskDao, $userDao, $configPrefix = null)
    {
        // generate an config file for this run
        $configs = array();
        $midasPath = Zend_Registry::get('webroot');
        $configs[] = 'url http://'.$_SERVER['HTTP_HOST'].$midasPath;
        $configs[] = 'appname Default';

        $email = $userDao->getEmail();
        // get an api key for this user
        /** @var UserapiModel $userApiModel */
        $userApiModel = MidasLoader::loadModel('Userapi');
        $userApiDao = $userApiModel->getByAppAndUser('Default', $userDao);
        if (!$userApiDao) {
            throw new Zend_Exception('You need to create a web API key for this user for application: Default');
        }
        $configs[] = 'email '.$email;
        $configs[] = 'apikey '.$userApiDao->getApikey();
        if ($configPrefix !== null) {
            $filepath = $taskDao->getWorkDir().'/'.$configPrefix.'config.cfg';
        } else {
            $filepath = $taskDao->getWorkDir().'/'.'config.cfg';
        }

        if (!file_put_contents($filepath, implode("\n", $configs))) {
            throw new Zend_Exception('Unable to write configuration file: '.$filepath);
        }
    }

    /**
     * will generate a batchmake config file in the work dir.
     *
     * @param Batchmake_TaskDao $taskDao
     * @param array $appTaskConfigProperties list of name=>value to be exported
     * @param string $condorPostScriptPath full path to the condor post script
     * @param string $condorDagPostScriptPath full path to condor dag post script
     * @param string $configScriptStem name of the batchmake script
     * @throws Zend_Exception
     */
    public function generateBatchmakeConfig(
        $taskDao,
        $appTaskConfigProperties,
        $condorPostScriptPath,
        $condorDagPostScriptPath,
        $configScriptStem
    ) {
        $configFileLines = array();

        foreach ($appTaskConfigProperties as $varName => $varValue) {
            if (is_array($varValue)) {
                $configFileLine = "Set(".$varName." ";
                $values = array();
                foreach ($varValue as $indVarValue) {
                    $values[] = "'".$indVarValue."'";
                }
                $configFileLine .= implode(" ", $values);
                $configFileLine .= ")";
                $configFileLines[] = $configFileLine;
            } else {
                $configFileLines[] = "Set(".$varName." '".$varValue."')";
            }
        }
        $configFileLines[] = "Set(cfg_condorpostscript '".$condorPostScriptPath."')";
        $configFileLines[] = "Set(cfg_output_directory '".$taskDao->getWorkDir()."')";
        $configFileLines[] = "Set(cfg_exe '/usr/bin/python')";
        $configFileLines[] = "Set(cfg_condordagpostscript '".$condorDagPostScriptPath."')";
        $configFileLines[] = "Set(cfg_taskID '".$taskDao->getBatchmakeTaskId()."')";

        $configFilePath = $taskDao->getWorkDir()."/".$configScriptStem.".config.bms";
        if (!file_put_contents($configFilePath, implode("\n", $configFileLines))
        ) {
            throw new Zend_Exception('Unable to write configuration file: '.$configFilePath);
        }
    }
}
