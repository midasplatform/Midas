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

/** Paraview Controller */
class Visualize_ParaviewController extends Visualize_AppController
{
    public $_models = array('Bitstream', 'Item', 'ItemRevision', 'Setting');
    public $_moduleComponents = array('Main');

    /**
     * Surface (mesh) model viewer action
     *
     * @param itemId The id of the item to view
     * @throws Zend_Exception
     */
    public function surfaceAction()
    {
        $itemid = $this->getParam('itemId');
        if (!isset($itemid)) {
            throw new Zend_Exception('Must specify an itemId parameter');
        }
        $item = $this->Item->load($itemid);

        if ($item === false || !$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            throw new Zend_Exception("This item doesn't exist or you don't have the permissions.");
        }
        $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/pqUnstructuredGrid16.png" />';
        $header .= ' Surface view: <a href="'.$this->view->webroot.'/item/'.$itemid.'">'.$item->getName().'</a>';
        $this->view->header = $header;

        $paraViewWorkDirectory = $this->Setting->getValueByName(VISUALIZE_PARAVIEW_WEB_WORK_DIRECTORY_KEY, $this->moduleName);
        $useParaView = $this->Setting->getValueByName(VISUALIZE_USE_PARAVIEW_WEB_KEY, $this->moduleName);
        $useWebGL = $this->Setting->getValueByName(VISUALIZE_USE_WEB_GL_KEY, $this->moduleName);
        $useSymlinks = $this->Setting->getValueByName(VISUALIZE_USE_SYMLINKS_KEY, $this->moduleName);
        $pwApp = $this->Setting->getValueByName(VISUALIZE_TOMCAT_ROOT_URL_KEY, $this->moduleName);

        if (!isset($useParaView) || !$useParaView) {
            throw new Zend_Exception('Please enable the use of a ParaViewWeb server on the module configuration page');
        }

        if (!isset($paraViewWorkDirectory) || empty($paraViewWorkDirectory)) {
            throw new Zend_Exception('Please set the ParaView work directory');
        }

        $pathArray = $this->ModuleComponent->Main->createParaviewPath();
        $path = $pathArray['path'];
        $tmpFolderName = $pathArray['folderName'];

        $revision = $this->Item->getLastRevision($item);
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
            }
        }

        if (!$useWebGL || $item->getSizebytes() > 1 * 1024 * 1024) {
            $this->view->renderer = 'js';
        } else {
            $this->view->renderer = 'webgl';
        }
        $this->view->json['visualize']['url'] = $filePath;
        $this->view->json['visualize']['item'] = $item;
        $this->view->json['visualize']['hostname'] = $this->_getHostName();
        $this->view->json['visualize']['wsport'] = $this->_getTomcatPort($pwApp);
        $this->view->fileLocation = $filePath;
        $this->view->jsImports = array(); // TODO
        $this->view->usewebgl = $useWebGL;
        $this->view->itemDao = $item;
    }

    /**
     * Show a parallel projection slice view of the volume with locked camera controls
     *
     * @param left The id of the item to visualize on the left
     * @param right The id of the item to visualize on the right
     * @param operations (Optional) Actions to allow from the slice view, separated by ;
     * @param jsImports (Optional) List of javascript files to import. These should contain handler
     *                             functions for imported operations. Separated by ;
     */
    public function dualAction()
    {
        $operations = $this->getParam('operations');
        if (!isset($operations)) {
            $operations = '';
        }

        $jsImports = $this->getParam('jsImports');
        if (isset($jsImports)) {
            $this->view->jsImports = explode(';', $jsImports);
        } else {
            $this->view->jsImports = array();
        }

        $left = $this->Item->load($this->getParam('left'));
        $right = $this->Item->load($this->getParam('right'));

        if ($left === false || !$this->Item->policyCheck($left, $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            throw new Zend_Exception("Left item doesn't exist or you don't have the permissions.");
        }
        if ($right === false || !$this->Item->policyCheck($right, $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            throw new Zend_Exception("Right item doesn't exist or you don't have the permissions.");
        }
        $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/application_tile_horizontal.png" />';
        $header .= ' Side-by-side view: <a href="'.$this->view->webroot.'/item/'.$left->getKey().'">'.$left->getName(
            ).'</a> | ';
        $header .= '<a href="'.$this->view->webroot.'/item/'.$right->getKey().'">'.$right->getName().'</a>';
        $this->view->header = $header;


        $paraViewWorkDirectory = $this->Setting->getValueByName(VISUALIZE_PARAVIEW_WEB_WORK_DIRECTORY_KEY, $this->moduleName);
        $useParaView = $this->Setting->getValueByName(VISUALIZE_USE_PARAVIEW_WEB_KEY, $this->moduleName);
        $useSymlinks = $this->Setting->getValueByName(VISUALIZE_USE_SYMLINKS_KEY, $this->moduleName);
        $pwApp = $this->Setting->getValueByName(VISUALIZE_TOMCAT_ROOT_URL_KEY, $this->moduleName);


        if (!isset($useParaView) || !$useParaView) {
            throw new Zend_Exception('Please enable the use of a ParaViewWeb server on the module configuration page');
        }

        if (!isset($paraViewWorkDirectory) || empty($paraViewWorkDirectory)) {
            throw new Zend_Exception('Please set the ParaView work directory');
        }

        $pathArray = $this->ModuleComponent->Main->createParaviewPath();
        $path = $pathArray['path'];

        $items = array('left' => $left, 'right' => $right);
        foreach ($items as $side => $item) {
            $subPath = $path.'/'.$side;
            mkdir($subPath);
            $revision = $this->Item->getLastRevision($item);
            if ($revision === false) {
                throw new Zend_Exception('The item has no revisions', MIDAS_INVALID_POLICY);
            }
            $bitstreams = $revision->getBitstreams();
            foreach ($bitstreams as $bitstream) {
                if ($useSymlinks) {
                    symlink($bitstream->getFullPath(), $subPath.'/'.$bitstream->getName());
                } else {
                    copy($bitstream->getFullPath(), $subPath.'/'.$bitstream->getName());
                }

                $ext = strtolower(substr(strrchr($bitstream->getName(), '.'), 1));
                switch ($ext) {
                    case 'mha':
                        $colorArrayNames[$side] = 'MetaImage';
                        break;
                    case 'nrrd':
                        $colorArrayNames[$side] = 'ImageFile';
                        break;
                    default:
                        break;
                }
                if ($ext != 'pvsm') {
                    $filePaths[$side] = $subPath.'/'.$bitstream->getName();
                }
            }
        }

        $this->view->json['visualize']['urls'] = $filePaths;
        $this->view->json['visualize']['operations'] = $operations;
        $this->view->json['visualize']['colorArrayNames'] = $colorArrayNames;
        $this->view->json['visualize']['items'] = $items;
        $this->view->json['visualize']['hostname'] = $this->_getHostName();
        $this->view->json['visualize']['wsport'] = $this->_getTomcatPort($pwApp);
        $this->view->operations = $operations;
        $this->view->fileLocations = $filePaths;
        $this->view->items = $items;
    }

    /**
     * Display a volume rendering of the selected item
     *
     * @param itemId The id of the MetaImage item to visualize
     * @param jsImports (Optional) List of javascript files to import. These should contain handler
     *                             functions for imported operations. Separated by ;
     */
    public function volumeAction()
    {
        $jsImports = $this->getParam('jsImports');
        if (isset($jsImports)) {
            $this->view->jsImports = explode(';', $jsImports);
        } else {
            $this->view->jsImports = array();
        }

        $meshes = $this->getParam('meshes');
        if (isset($meshes)) {
            $meshes = explode(';', $meshes);
        } else {
            $meshes = array();
        }

        $itemid = $this->getParam('itemId');
        $item = $this->Item->load($itemid);
        if ($item === false || !$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            throw new Zend_Exception("This item doesn't exist or you don't have the permissions.");
        }
        $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/volume.png" />';
        $header .= ' Volume rendering: <a href="'.$this->view->webroot.'/item/'.$itemid.'">'.$item->getName().'</a>';
        $this->view->header = $header;

        $paraViewWorkDirectory = $this->Setting->getValueByName(VISUALIZE_PARAVIEW_WEB_WORK_DIRECTORY_KEY, $this->moduleName);
        $useParaView = $this->Setting->getValueByName(VISUALIZE_USE_PARAVIEW_WEB_KEY, $this->moduleName);
        $useSymlinks = $this->Setting->getValueByName(VISUALIZE_USE_SYMLINKS_KEY, $this->moduleName);
        $pwApp = $this->Setting->getValueByName(VISUALIZE_TOMCAT_ROOT_URL_KEY, $this->moduleName);

        if (!isset($useParaView) || !$useParaView) {
            throw new Zend_Exception('Please enable the use of a ParaViewWeb server on the module configuration page');
        }

        if (!isset($paraViewWorkDirectory) || empty($paraViewWorkDirectory)) {
            throw new Zend_Exception('Please set the ParaView work directory');
        }

        $pathArray = $this->ModuleComponent->Main->createParaviewPath();
        $path = $pathArray['path'];
        $tmpFolderName = $pathArray['folderName'];

        $revision = $this->Item->getLastRevision($item);
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
            switch ($ext) {
                case 'mha':
                    $colorArrayName = 'MetaImage';
                    break;
                case 'nrrd':
                    $colorArrayName = 'ImageFile';
                    break;
                default:
                    break;
            }
            if ($ext != 'pvsm') {
                $filePath = $paraViewWorkDirectory.'/'.$tmpFolderName.'/'.$bitstream->getName();
            }
        }

        // Load in other mesh sources
        $meshObj = array();
        foreach ($meshes as $meshId) {
            $otherItem = $this->Item->load($meshId);
            if ($otherItem === false || !$this->Item->policyCheck(
                    $otherItem,
                    $this->userSession->Dao,
                    MIDAS_POLICY_READ
                )
            ) {
                throw new Zend_Exception("This item doesn't exist or you don't have the permissions.");
            }
            $revision = $this->Item->getLastRevision($otherItem);
            if ($revision === false) {
                throw new Zend_Exception('The item has no revisions', MIDAS_INVALID_POLICY);
            }
            $bitstreams = $revision->getBitstreams();
            foreach ($bitstreams as $bitstream) {
                $otherFile = $path.'/'.$bitstream->getName();
                if ($useSymlinks) {
                    symlink($bitstream->getFullPath(), $otherFile);
                } else {
                    copy($bitstream->getFullPath(), $otherFile);
                }
                // Use metadata values for mesh color and orientation if they exist
                $metadata = $this->ItemRevision->getMetadata($revision);
                $diffuseColor = array(1.0, 0.0, 0.0); // default to red mesh
                $orientation = array(0.0, 0.0, 0.0); // default to no orientation transform
                foreach ($metadata as $metadatum) {
                    if (strtolower($metadatum->getElement()) == 'visualize') {
                        if (strtolower($metadatum->getQualifier()) == 'diffusecolor'
                        ) {
                            try { // must be json encoded, otherwise we ignore it and use the default
                                $diffuseColor = json_decode($metadatum->getValue());
                            } catch (Exception $e) {
                                $this->getLogger()->warn('Invalid diffuseColor metadata value (id='.$meshId.')');
                            }
                        }
                        if (strtolower($metadatum->getQualifier()) == 'orientation'
                        ) {
                            try { // must be json encoded, otherwise we ignore it and use the default
                                $orientation = json_decode($metadatum->getValue());
                            } catch (Exception $e) {
                                $this->getLogger()->warn('Invalid orientation metadata value (id='.$meshId.')');
                            }
                        }
                    }
                }

                $meshObj[] = array(
                    'path' => $otherFile,
                    'item' => $otherItem,
                    'visible' => true,
                    'diffuseColor' => $diffuseColor,
                    'orientation' => $orientation,
                );
            }
        }

        $this->view->json['visualize']['url'] = $filePath;
        $this->view->json['visualize']['meshes'] = $meshObj;
        $this->view->json['visualize']['item'] = $item;
        $this->view->json['visualize']['visible'] = true;
        $this->view->json['visualize']['colorArrayName'] = $colorArrayName;
        $this->view->json['visualize']['hostname'] = $this->_getHostName();
        $this->view->json['visualize']['wsport'] = $this->_getTomcatPort($pwApp);
        $this->view->fileLocation = $filePath;
        $this->view->itemDao = $item;
    }

    /**
     * Use the axial slice view mode for MetaImage volume data
     *
     * @param itemId The id of the MetaImage item to visualize
     * @param operations (Optional) Actions to allow from the slice view, separated by ;
     * @param jsImports (Optional) List of javascript files to import. These should contain handler
     *                             functions for imported operations. Separated by ;
     * @param meshes (Optional) List of item ids to also load into the scene as meshes
     */
    public function sliceAction()
    {
        $operations = $this->getParam('operations');
        if (!isset($operations)) {
            $operations = '';
        }

        $jsImports = $this->getParam('jsImports');
        if (isset($jsImports)) {
            $this->view->jsImports = explode(';', $jsImports);
        } else {
            $this->view->jsImports = array();
        }

        $meshes = $this->getParam('meshes');
        if (isset($meshes)) {
            $meshes = explode(';', $meshes);
        } else {
            $meshes = array();
        }

        $itemid = $this->getParam('itemId');
        $item = $this->Item->load($itemid);
        if ($item === false || !$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ)
        ) {
            throw new Zend_Exception("This item doesn't exist or you don't have the permissions.");
        }
        $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/sliceView.png" />';
        $header .= ' Slice view: <a href="'.$this->view->webroot.'/item/'.$itemid.'">'.$item->getName().'</a>';
        $this->view->header = $header;

        $paraViewWorkDirectory = $this->Setting->getValueByName(VISUALIZE_PARAVIEW_WEB_WORK_DIRECTORY_KEY, $this->moduleName);
        $useParaView = $this->Setting->getValueByName(VISUALIZE_USE_PARAVIEW_WEB_KEY, $this->moduleName);
        $useSymlinks = $this->Setting->getValueByName(VISUALIZE_USE_SYMLINKS_KEY, $this->moduleName);
        $pwApp = $this->Setting->getValueByName(VISUALIZE_TOMCAT_ROOT_URL_KEY, $this->moduleName);

        if (!isset($useParaView) || !$useParaView) {
            throw new Zend_Exception('Please enable the use of a ParaViewWeb server on the module configuration page');
        }

        if (!isset($paraViewWorkDirectory) || empty($paraViewWorkDirectory)) {
            throw new Zend_Exception('Please set the ParaView work directory');
        }

        $pathArray = $this->ModuleComponent->Main->createParaviewPath();
        $path = $pathArray['path'];
        $tmpFolderName = $pathArray['folderName'];

        $revision = $this->Item->getLastRevision($item);
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
            switch ($ext) {
                case 'mha':
                    $colorArrayName = 'MetaImage';
                    break;
                case 'nrrd':
                    $colorArrayName = 'ImageFile';
                    break;
                default:
                    break;
            }
            if ($ext != 'pvsm') {
                $filePath = $paraViewWorkDirectory.'/'.$tmpFolderName.'/'.$bitstream->getName();
            }
        }

        // Load in other mesh sources
        $meshObj = array();
        foreach ($meshes as $meshId) {
            $otherItem = $this->Item->load($meshId);
            if ($otherItem === false || !$this->Item->policyCheck(
                    $otherItem,
                    $this->userSession->Dao,
                    MIDAS_POLICY_READ
                )
            ) {
                throw new Zend_Exception("This item doesn't exist or you don't have the permissions.");
            }
            $revision = $this->Item->getLastRevision($otherItem);
            if ($revision === false) {
                throw new Zend_Exception('The item has no revisions', MIDAS_INVALID_POLICY);
            }
            $bitstreams = $revision->getBitstreams();
            foreach ($bitstreams as $bitstream) {
                $otherFile = $path.'/'.$bitstream->getName();
                if ($useSymlinks) {
                    symlink($bitstream->getFullPath(), $otherFile);
                } else {
                    copy($bitstream->getFullPath(), $otherFile);
                }
            }
            // Use metadata values for mesh color and orientation if they exist
            $metadata = $this->ItemRevision->getMetadata($revision);
            $diffuseColor = array(1.0, 0.0, 0.0); // default to red mesh
            $orientation = array(0.0, 0.0, 0.0); // default to no orientation transform
            foreach ($metadata as $metadatum) {
                if (strtolower($metadatum->getElement()) == 'visualize') {
                    if (strtolower($metadatum->getQualifier()) == 'diffusecolor'
                    ) {
                        try { // must be json encoded, otherwise we ignore it and use the default
                            $diffuseColor = json_decode($metadatum->getValue());
                        } catch (Exception $e) {
                            $this->getLogger()->warn('Invalid diffuseColor metadata value (id='.$meshId.')');
                        }
                    }
                    if (strtolower($metadatum->getQualifier()) == 'orientation'
                    ) {
                        try { // must be json encoded, otherwise we ignore it and use the default
                            $orientation = json_decode($metadatum->getValue());
                        } catch (Exception $e) {
                            $this->getLogger()->warn('Invalid orientation metadata value (id='.$meshId.')');
                        }
                    }
                }
            }
            $meshObj[] = array(
                'path' => $otherFile,
                'item' => $otherItem,
                'visible' => true,
                'diffuseColor' => $diffuseColor,
                'orientation' => $orientation,
            );
        }

        $this->view->json['visualize']['url'] = $filePath;
        $this->view->json['visualize']['operations'] = $operations;
        $this->view->json['visualize']['meshes'] = $meshObj;
        $this->view->json['visualize']['colorArrayName'] = $colorArrayName;
        $this->view->json['visualize']['item'] = $item;
        $this->view->json['visualize']['hostname'] = $this->_getHostName();
        $this->view->json['visualize']['wsport'] = $this->_getTomcatPort($pwApp);
        $this->view->operations = $operations;
        $this->view->fileLocation = $filePath;
        $this->view->itemDao = $item;
    }

    /**
     * Helper method to pass the server host name to json for using web socket renderer
     */
    protected function _getHostName()
    {
        if ($this->isTestingEnv()) {
            return 'localhost';
        } else {
            return empty($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['HTTP_X_FORWARDED_HOST'];
        }
    }

    /**
     * Helper function to extract the port Tomcat is listening on
     */
    protected function _getTomcatPort($pwapp)
    {
        if (preg_match('/:([0-9]+)\//', $pwapp, $matches)) {
            return $matches[1];
        } else {
            return '80';
        }
    }
}
