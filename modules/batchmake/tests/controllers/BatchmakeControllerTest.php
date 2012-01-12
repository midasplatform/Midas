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

// need to include the module constant for this test
require_once BASE_PATH.'/modules/batchmake/constant/module.php';
require_once BASE_PATH.'/modules/batchmake/controllers/components/KWBatchmakeComponent.php';
require_once BASE_PATH.'/library/KWUtils.php';

/** helper class used for testing batchmake module */
class BatchmakeControllerTest extends ControllerTestCase
  {

  /**
   * setup function will set test config properties in the Zend_Registry
   */
  public function setup()
    {
    parent::setup();
    $testConfigProps = $this->setupAndGetConfig();
    Zend_Registry::set('batchmake_test_config', $testConfigProps);
    }

  /**
   * function will create a temporary batchmake config, copying over test data
   * to the the locations in that config needed for the tests, returning an
   * array of config property names to directory locations.
   * @TODO figure out a way to copy over Batchmake or else mock it
   * @return string
   */
  public function setupAndGetConfig()
    {
    // create a test batchmake setup in the temp dir
    // and initialize test data
    $tmpDir = $this->getTempDirectory() .'/';
    $subDirs = array("batchmake", "tests");
    $testDir = KWUtils::createSubDirectories($tmpDir, $subDirs);
    $configProps = array(MIDAS_BATCHMAKE_TMP_DIR_PROPERTY => $tmpDir.'/batchmake/tests/tmp',
    MIDAS_BATCHMAKE_BIN_DIR_PROPERTY => $tmpDir.'/batchmake/tests/bin',
    MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY => $tmpDir.'/batchmake/tests/script',
    MIDAS_BATCHMAKE_APP_DIR_PROPERTY => $tmpDir.'/batchmake/tests/bin',
    MIDAS_BATCHMAKE_DATA_DIR_PROPERTY => $tmpDir.'/batchmake/tests/data',
    MIDAS_BATCHMAKE_CONDOR_BIN_DIR_PROPERTY => $tmpDir.'/batchmake/tests/condorbin');
    // now make sure these dirs exist
    // later can actually add some stuff to these dirs
    foreach($configProps as $prop => $dir)
      {
      if(!file_exists($dir) && !KWUtils::mkDir($dir))
        {
        throw new Zend_Exception("couldn't create dir ".$dir);
        }
      }

    // now copy over the bms files
    $srcDir = BASE_PATH . 'modules/batchmake/tests/testfiles/script';
    $targetDir = $configProps[MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY];
    $extension = '.bms';
    $this->symlinkFileset($srcDir, $targetDir, $extension);

    // and now the bmms
    $srcDir = BASE_PATH . 'modules/batchmake/tests/testfiles/bin';
    $targetDir = $configProps[MIDAS_BATCHMAKE_APP_DIR_PROPERTY];
    $extension = '.bmm';
    $this->symlinkFileset($srcDir, $targetDir, $extension);


    // the mock object strategy requires both an interface and for
    // executable files to exist on disk in a particular location,
    // so here we will create symlinks to a known executable
    // ls
    // which should be on most systems

    $params = array('ls');
    $cmd = KWUtils::prepareExeccommand('which', $params);
    // dir doesn't matter, just send in srcDir as it is convenient
    KWUtils::exec($cmd, $output, $srcDir, $returnVal);
    if($returnVal !== 0 || !isset($output) || !isset($output[0]))
      {
      throw new Zend_Exception('Problem finding ls on your system, used for testing');
      }
    $pathToLs = $output[0];

    // get the applications and their path properties from the component that
    // expects them
    $applicationsPaths = Batchmake_KWBatchmakeComponent::getApplicationsPaths();
    foreach($applicationsPaths as $application => $pathProperty)
      {
      // now in the place of each executable, symlink the ls exe
      $link = $configProps[$pathProperty] . '/' . $application;
      if(!file_exists($link) && !symlink($pathToLs, $link))
        {
        throw new Zend_Exception($pathToLs . ' could not be sym-linked to ' . $link);
        }
      }

    return $configProps;
    }

  /**
   * looks in the srcDir, finds all files ending with $extension, and
   * symlinks them to targetDir if there isn't already a file there
   * by that name
   * @param type $srcDir
   * @param type $targetDir
   * @param type $extension
   */
  protected function symlinkFileset($srcDir, $targetDir, $extension)
    {
    // open the directory
    $handle = opendir($srcDir);
    if(!is_readable($srcDir))
      {
      throw new Zend_Exception("can't read ".$srcDir);
      }
    // and scan through the items inside
    while(false !== ($item = readdir($handle)))
      {
      // make sure item matches extendsion
      if((strpos($item, $extension) == strlen($item) - 4))
        {
        // link the file if it isn't already there
        $scriptTarget = $srcDir . '/' . $item;
        $scriptLink = $targetDir . '/' . $item;
        if(!file_exists($scriptLink) && !symlink($scriptTarget, $scriptLink))
          {
          throw new Zend_Exception($scriptTarget . ' could not be sym-linked to ' . $scriptLink);
          }
        }
      }
    closedir($handle);
    }

  }
