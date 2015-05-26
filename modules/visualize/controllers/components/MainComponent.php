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

require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';

/** main  component */
class Visualize_MainComponent extends AppComponent
{
    public $moduleName = 'visualize';

    /** convert to threejs */
    public function convertToThreejs($revision)
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $useWebGL = $settingModel->getValueByName(VISUALIZE_USE_WEB_GL_KEY, $this->moduleName);

        if (!isset($useWebGL) || !$useWebGL) {
            return false;
        }

        /** @var ItemRevisionModel $itemRevisionModel */
        $itemRevisionModel = MidasLoader::loadModel('ItemRevision');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        if (is_array($revision)) {
            $revision = $itemRevisionModel->load($revision['itemrevision_id']);
        }

        $bitstreams = $revision->getBitstreams();
        $item = $revision->getItem();
        $parents = $item->getFolders();
        $userDao = $revision->getUser();
        if (count($parents) == 0) {
            return;
        }
        $parent = $parents[0];
        if (count($bitstreams) != 1) {
            return;
        }
        $bitstream = $bitstreams[0];
        $filenameArray = explode('.', $bitstream->getName());
        $ext = end($filenameArray);
        if ($ext != 'obj') {
            return;
        }

        if (file_exists(UtilityComponent::getTempDirectory().'/tmpThreeJs.js')) {
            unlink(UtilityComponent::getTempDirectory().'/tmpThreeJs.js');
        }
        exec(
            'python '.dirname(__FILE__).'/scripts/convert_obj_three.py -i '.$bitstream->GetFullPath(
            ).' -o '.UtilityComponent::getTempDirectory().'/tmpThreeJs.js -t binary',
            $output
        );
        if (file_exists(UtilityComponent::getTempDirectory().'/tmpThreeJs.js') && file_exists(
                UtilityComponent::getTempDirectory().'/tmpThreeJs.bin'
            )
        ) {
            /** @var AssetstoreModel $assetstoreModel */
            $assetstoreModel = MidasLoader::loadModel('Assetstore');
            $assetstoreDao = $assetstoreModel->getDefault();

            /** @var UploadComponent $uploadComponent */
            $uploadComponent = MidasLoader::loadComponent('Upload');

            $newItem = $uploadComponent->createUploadedItem(
                $userDao,
                $item->getName().'.threejs.bin',
                UtilityComponent::getTempDirectory().'/tmpThreeJs.bin',
                $parent
            );
            $itemModel->copyParentPolicies($newItem, $parent);
            $newRevision = $itemModel->getLastRevision($newItem);
            if ($newRevision === false) {
                throw new Zend_Exception('The item has no revisions', MIDAS_INVALID_POLICY);
            }
            $bitstreams = $newRevision->getBitstreams();
            if (count($bitstreams) === 0) {
                throw new Zend_Exception('The item has no bitstreams', MIDAS_INVALID_POLICY);
            }
            $bitstreamDao = $bitstreams[0];

            Zend_Loader::loadClass('BitstreamDao', BASE_PATH.'/core/models/dao');
            $content = file_get_contents(UtilityComponent::getTempDirectory().'/tmpThreeJs.js');
            $fc = Zend_Controller_Front::getInstance();
            $content = str_replace(
                'tmpThreeJs.bin',
                $fc->getBaseUrl().'/download/?bitstream='.$bitstreamDao->getKey(),
                $content
            );
            file_put_contents(UtilityComponent::getTempDirectory().'/tmpThreeJs.js', $content);

            $bitstreamDao = new BitstreamDao();
            $bitstreamDao->setName($item->getName().'.threejs.js');
            $bitstreamDao->setPath(UtilityComponent::getTempDirectory().'/tmpThreeJs.js');
            $bitstreamDao->setChecksum('');
            $bitstreamDao->fillPropertiesFromPath();
            $bitstreamDao->setAssetstoreId($assetstoreDao->getKey());

            // Upload the bitstream if necessary (based on the assetstore type)
            $uploadComponent->uploadBitstream($bitstreamDao, $assetstoreDao);
            $itemRevisionModel->addBitstream($newRevision, $bitstreamDao);
        }
    }

    /** Test whether we can visualize with slice viewer */
    public function canVisualizeWithSliceView($itemDao)
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $useParaView = $settingModel->getValueByName(VISUALIZE_USE_PARAVIEW_WEB_KEY, $this->moduleName);

        if (!isset($useParaView) || !$useParaView) {
            return false;
        }

        $extensions = array('mha', 'nrrd');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $revision = $itemModel->getLastRevision($itemDao);
        if ($revision === false) {
            return false;
        }
        $bitstreams = $revision->getBitstreams();
        if (count($bitstreams) === 0) {
            return false;
        }

        $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));

        return in_array($ext, $extensions);
    }

    /** Test whether we can visualize with surface model viewer */
    public function canVisualizeWithSurfaceView($itemDao)
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $useParaView = $settingModel->getValueByName(VISUALIZE_USE_PARAVIEW_WEB_KEY, $this->moduleName);

        if (!isset($useParaView) || !$useParaView) {
            return false;
        }

        $extensions = array('vtk', 'vtp', 'ply');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $revision = $itemModel->getLastRevision($itemDao);
        if ($revision === false) {
            return false;
        }
        $bitstreams = $revision->getBitstreams();
        if (count($bitstreams) === 0) {
            return false;
        }

        $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));

        return in_array($ext, $extensions);
    }

    /** can visualize */
    public function canVisualizeWithParaview($itemDao)
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $useParaView = $settingModel->getValueByName(VISUALIZE_USE_PARAVIEW_WEB_KEY, $this->moduleName);

        if (!isset($useParaView) || !$useParaView) {
            return false;
        }

        $extensions = array('vtk', 'ply', 'vtp', 'pvsm', 'mha', 'vtu');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $revision = $itemModel->getLastRevision($itemDao);
        if ($revision === false) {
            return false;
        }
        $bitstreams = $revision->getBitstreams();
        if (count($bitstreams) === 0) {
            return false;
        }

        $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));

        return in_array($ext, $extensions);
    }

    /** visualize */
    public function canVisualizeTxt($itemDao)
    {
        $extensions = array('txt', 'php', 'js', 'html', 'cpp', 'java', 'py', 'h', 'log');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $revision = $itemModel->getLastRevision($itemDao);
        if ($revision === false) {
            return false;
        }
        $bitstreams = $revision->getBitstreams();
        if (count($bitstreams) === 0) {
            return false;
        }

        $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));

        return in_array($ext, $extensions);
    }

    /** visualize */
    public function canVisualizeWebgl($itemDao)
    {
        $extensions = array('bin');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $revision = $itemModel->getLastRevision($itemDao);
        if ($revision === false) {
            return false;
        }
        $bitstreams = $revision->getBitstreams();
        if (strpos($itemDao->getName(), 'threejs') === false) {
            return false;
        }

        if (count($bitstreams) != 2) {
            return false;
        }

        $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));

        return in_array($ext, $extensions);
    }

    /** visualize */
    public function canVisualizePdf($itemDao)
    {
        $extensions = array('pdf');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $revision = $itemModel->getLastRevision($itemDao);
        if ($revision === false) {
            return false;
        }
        $bitstreams = $revision->getBitstreams();
        if (count($bitstreams) === 0) {
            return false;
        }

        $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));

        return in_array($ext, $extensions);
    }

    /** visualize */
    public function canVisualizeImage($itemDao)
    {
        $extensions = array('jpg', 'jpeg', 'gif', 'bmp', 'png');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $revision = $itemModel->getLastRevision($itemDao);
        if ($revision === false) {
            return false;
        }
        $bitstreams = $revision->getBitstreams();
        if (count($bitstreams) === 0) {
            return false;
        }

        $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));

        return in_array($ext, $extensions);
    }

    /** can visualize */
    public function canVisualizeMedia($itemDao)
    {
        $extensions = array('m4a', 'm4v', 'mp3', 'mp4', 'avi');

        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        $revision = $itemModel->getLastRevision($itemDao);
        if ($revision === false) {
            return false;
        }
        $bitstreams = $revision->getBitstreams();
        if (count($bitstreams) === 0) {
            return false;
        }

        $ext = strtolower(substr(strrchr($bitstreams[0]->getName(), '.'), 1));

        return in_array($ext, $extensions);
    }

    /** processParaviewData */
    public function processParaviewData($itemDao)
    {
        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');
        if (!is_object($itemDao)) {
            $itemDao = $itemModel->load($itemDao['item_id']);
        }
        if (!$this->canVisualizeWithParaview($itemDao)) {
            return;
        }

        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $paraViewWorkDirectory = $settingModel->getValueByName(VISUALIZE_PARAVIEW_WEB_WORK_DIRECTORY_KEY, $this->moduleName);
        $useSymlinks = $settingModel->getValueByName(VISUALIZE_USE_SYMLINKS_KEY, $this->moduleName);
        $pwApp = $settingModel->getValueByName(VISUALIZE_TOMCAT_ROOT_URL_KEY, $this->moduleName);
        $pvBatch = $settingModel->getValueByName(VISUALIZE_PVBATCH_COMMAND_KEY, $this->moduleName);

        if (empty($pwApp) || empty($pvBatch)) {
            return;
        }

        $pathArray = $this->createParaviewPath();
        $path = $pathArray['path'];
        $tmpFolderName = $pathArray['folderName'];

        $revision = $itemModel->getLastRevision($itemDao);
        if ($revision === false) {
            throw new Zend_Exception('The item has no revisions', MIDAS_INVALID_POLICY);
        }
        $bitstreams = $revision->getBitstreams();
        foreach ($bitstreams as $bitstream) {
            if ($useSymlinks) {
                symlink($bitstream->getFullPath(), $path.'/'.$bitstream->getName());
            } else {
                copy($bitstream->getFullPath(), $path.'/'.$bitstream->getName());
            }

            $ext = strtolower(substr(strrchr($bitstream->getName(), '.'), 1));
            if ($ext != 'pvsm') {
                $filePath = $paraViewWorkDirectory.'/'.$tmpFolderName.'/'.$bitstream->getName();
                $mainBitstream = $bitstream;
            }
        }

        foreach ($bitstreams as $bitstream) {
            $ext = strtolower(substr(strrchr($bitstream->getName(), '.'), 1));
            if ($ext == 'pvsm') {
                $file_contents = file_get_contents($path.'/'.$bitstream->getName());
                $file_contents = preg_replace(
                    '/\"([a-zA-Z0-9_.\/\\\:]{1,1000})'.str_replace('.', '\.', $mainBitstream->getName()).'/',
                    '"'.$filePath,
                    $file_contents
                );
                $filePath = $paraViewWorkDirectory.'/'.$tmpFolderName.'/'.$bitstream->getName();
                $inF = fopen($path.'/'.$bitstream->getName(), 'w');
                fwrite($inF, $file_contents);
                fclose($inF);
                $this->view->json['visualize']['openState'] = true;
                break;
            }
        }

        $tmpPath = UtilityComponent::getTempDirectory();
        if (file_exists($tmpPath.'/screenshot1.png')) {
            unlink($tmpPath.'/screenshot1.png');
        }
        if (file_exists($tmpPath.'/screenshot2.png')) {
            unlink($tmpPath.'/screenshot2.png');
        }
        if (file_exists($tmpPath.'/screenshot4.png')) {
            unlink($tmpPath.'/screenshot4.png');
        }
        if (file_exists($tmpPath.'/screenshot3.png')) {
            unlink($tmpPath.'/screenshot3.png');
        }

        $return = file_get_contents(
            str_replace('PWApp', 'processData', $pwApp).'?file='.$filePath.'&pvbatch='.$pvBatch
        );
        if (strpos($return, 'PROBLEME') !== false) {
            return;
        }
        copy(str_replace('PWApp', 'processData', $pwApp).'/screenshot1.png', $tmpPath.'/screenshot1.png');
        copy(str_replace('PWApp', 'processData', $pwApp).'/screenshot2.png', $tmpPath.'/screenshot2.png');
        copy(str_replace('PWApp', 'processData', $pwApp).'/screenshot4.png', $tmpPath.'/screenshot4.png');
        copy(str_replace('PWApp', 'processData', $pwApp).'/screenshot3.png', $tmpPath.'/screenshot3.png');

        $json = file_get_contents(str_replace('PWApp', 'processData', $pwApp).'/metadata.txt');

        $metadata = json_decode($json);

        /** @var MetadataModel $metadataModel */
        $metadataModel = MidasLoader::loadModel('Metadata');

        $metadataDao = $metadataModel->getMetadata(MIDAS_METADATA_TEXT, 'image', 'type');
        if (!$metadataDao) {
            $metadataModel->addMetadata(MIDAS_METADATA_TEXT, 'image', 'type', '');
        }
        $metadataModel->addMetadataValue($revision, MIDAS_METADATA_TEXT, 'image', 'type', $metadata[0]);

        $metadataDao = $metadataModel->getMetadata(MIDAS_METADATA_TEXT, 'image', 'points');
        if (!$metadataDao) {
            $metadataModel->addMetadata(MIDAS_METADATA_TEXT, 'image', 'points', '');
        }
        $metadataModel->addMetadataValue($revision, MIDAS_METADATA_TEXT, 'image', 'points', $metadata[1]);

        $metadataDao = $metadataModel->getMetadata(MIDAS_METADATA_TEXT, 'image', 'cells');
        if (!$metadataDao) {
            $metadataModel->addMetadata(MIDAS_METADATA_TEXT, 'image', 'cells', '');
        }
        $metadataModel->addMetadataValue($revision, MIDAS_METADATA_TEXT, 'image', 'cells', $metadata[2]);

        $metadataDao = $metadataModel->getMetadata(MIDAS_METADATA_TEXT, 'image', 'polygons');
        if (!$metadataDao) {
            $metadataModel->addMetadata(MIDAS_METADATA_TEXT, 'image', 'polygons', '');
        }

        $metadataModel->addMetadataValue($revision, MIDAS_METADATA_TEXT, 'image', 'polygons', $metadata[3]);

        $metadataDao = $metadataModel->getMetadata(MIDAS_METADATA_TEXT, 'image', 'x-range');
        if (!$metadataDao) {
            $metadataModel->addMetadata(MIDAS_METADATA_TEXT, 'image', 'x-range', '');
        }

        $metadataModel->addMetadataValue(
            $revision,
            MIDAS_METADATA_TEXT,
            'image',
            'x-range',
            $metadata[4][0].' to '.$metadata[4][1]
        );
        $metadataDao = $metadataModel->getMetadata(MIDAS_METADATA_TEXT, 'image', 'y-range');
        if (!$metadataDao) {
            $metadataModel->addMetadata(MIDAS_METADATA_TEXT, 'image', 'y-range', '');
        }

        $metadataModel->addMetadataValue(
            $revision,
            MIDAS_METADATA_TEXT,
            'image',
            'y-range',
            $metadata[4][2].' to '.$metadata[4][3]
        );

        // create thumbnail
        try {
            $src = imagecreatefrompng($tmpPath.'/screenshot1.png');
        } catch (Exception $exc) {
            return;
        }

        /** @var RandomComponent $randomComponent */
        $randomComponent = MidasLoader::loadComponent('Random');
        $thumbnailPath = UtilityComponent::getDataDirectory('thumbnail').'/'.$randomComponent->generateInt();
        if (!file_exists(UtilityComponent::getDataDirectory('thumbnail'))) {
            throw new Zend_Exception('Problem thumbnail path: '.UtilityComponent::getDataDirectory('thumbnail'));
        }
        if (!file_exists($thumbnailPath)) {
            mkdir($thumbnailPath);
        }
        $thumbnailPath .= '/'.$randomComponent->generateInt();
        if (!file_exists($thumbnailPath)) {
            mkdir($thumbnailPath);
        }
        $destination = $thumbnailPath.'/'.$randomComponent->generateInt().'.jpg';
        while (file_exists($destination)) {
            $destination = $thumbnailPath.'/'.$randomComponent->generateInt().'.jpg';
        }
        $pathThumbnail = $destination;

        list($x, $y) = getimagesize($tmpPath.'/screenshot1.png');  //--- get size of img ---
        $thumb = 100;  //--- max. size of thumb ---
        if ($x > $y) {
            $tx = $thumb;  //--- landscape ---
            $ty = round($thumb / $x * $y);
        } else {
            $tx = round($thumb / $y * $x);  //--- portrait ---
            $ty = $thumb;
        }

        $thb = imagecreatetruecolor($tx, $ty);  //--- create thumbnail ---
        imagecopyresampled($thb, $src, 0, 0, 0, 0, $tx, $ty, $x, $y);
        imagejpeg($thb, $pathThumbnail, 80);
        imagedestroy($thb);
        imagedestroy($src);

        $oldThumbnail = $itemDao->getThumbnail();
        if (!empty($oldThumbnail)) {
            unlink($oldThumbnail);
        }
        $itemDao->setThumbnail(substr($pathThumbnail, strlen(BASE_PATH) + 1));
        $itemModel->save($itemDao);

        $data_dir = UtilityComponent::getDataDirectory('visualize');
        if (!file_exists($data_dir)) {
            mkdir($data_dir);
        }
        rename($tmpPath.'/screenshot1.png', $data_dir.'_'.$itemDao->getKey().'_1.png');
        rename($tmpPath.'/screenshot2.png', $data_dir.'_'.$itemDao->getKey().'_2.png');
        rename($tmpPath.'/screenshot3.png', $data_dir.'_'.$itemDao->getKey().'_3.png');
        rename($tmpPath.'/screenshot4.png', $data_dir.'_'.$itemDao->getKey().'_4.png');
    }

    /** createParaviewPath */
    public function createParaviewPath()
    {
        /** @var SettingModel $settingModel */
        $settingModel = MidasLoader::loadModel('Setting');
        $customTemporaryDirectory = $settingModel->getValueByName(VISUALIZE_TEMPORARY_DIRECTORY_KEY, $this->moduleName);

        if (isset($customTemporaryDirectory) && !empty($customTemporaryDirectory)) {
            $tmp_dir = $customTemporaryDirectory;
            if (!file_exists($tmp_dir) || !is_writable($tmp_dir)) {
                throw new Zend_Exception('Unable to access temp dir');
            }
        } else {
            if (!file_exists(UtilityComponent::getTempDirectory().'/visualize')
            ) {
                mkdir(UtilityComponent::getTempDirectory().'/visualize');
            }
            $tmp_dir = UtilityComponent::getTempDirectory().'/visualize';
        }

        $dir = opendir($tmp_dir);
        while ($entry = readdir($dir)) {
            if (is_dir($tmp_dir.'/'.$entry) && filemtime($tmp_dir.'/'.$entry) < strtotime(
                    '-1 hours'
                ) && !in_array($entry, array('.', '..'))
            ) {
                if (strpos($entry, 'Paraview') !== false) {
                    $this->_rrmdir($tmp_dir.'/'.$entry);
                }
            }
        }

        /** @var RandomComponent $randomComponent */
        $randomComponent = MidasLoader::loadComponent('Random');
        $tmpFolderName = 'ParaviewWeb_'.$randomComponent->generateInt();
        $path = $tmp_dir.'/'.$tmpFolderName;
        while (!mkdir($path)) {
            $tmpFolderName = 'ParaviewWeb_'.$randomComponent->generateInt();
            $path = $tmp_dir.'/'.$tmpFolderName;
        }

        return array('path' => $path, 'folderName' => $tmpFolderName);
    }

    /** recursively delete a folder */
    private function _rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
        }

        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (filetype($dir.'/'.$object) == 'dir') {
                    $this->_rrmdir($dir.'/'.$object);
                } else {
                    unlink($dir.'/'.$object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}
