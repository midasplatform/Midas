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
 * This component contains method for testing
 * whether a Midas item can be visualized using
 * various paraviewweb apps
 */
class Pvw_ValidationComponent extends AppComponent
{
    /** Test whether we can visualize with slice viewer */
    public function canVisualizeWithSliceView($itemDao)
    {
        return $this->_testItem($itemDao, array('mha', 'nrrd'));
    }

    /** Test whether we can visualize with surface model viewer */
    public function canVisualizeWithSurfaceView($itemDao)
    {
        return $this->_testItem($itemDao, array('vtk', 'vtp', 'ply', 'obj'));
    }

    /** Helper function to test if an item matches a list of extensions */
    private function _testItem($itemDao, $extensions)
    {
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
}
