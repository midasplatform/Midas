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

/** WebGL controller */
class Visualize_WebglController extends Visualize_AppController
{
    public $_components = array('Utility', 'Date');
    public $_models = array('Item', 'Folder');
    public $_moduleModels = array();

    /** index */
    public function indexAction()
    {
        $this->disableLayout();
        $folderid = $this->getParam('folder');
        $items = array();
        if (is_numeric($folderid)) {
            $folder = $this->Folder->load($folderid);
            $items = $folder->getItems();
        }

        $itemid = $this->getParam('itemId');
        if (is_numeric($itemid)) {
            $items[] = $this->Item->load($itemid);
        }

        $data = array();

        foreach ($items as $item) {
            if ($item === false || !$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_READ)
            ) {
                continue;
            }
            $revision = $this->Item->getLastRevision($item);
            $bitstreams = $revision->getBitstreams();
            if (count($bitstreams) == 1) {
                continue;
            }

            $binFound = false;
            $jsFile = false;

            foreach ($bitstreams as $b) {
                $ext = end(explode('.', $b->getName()));
                if ($ext == "bin") {
                    $binFound = true;
                }
                if ($ext == "js") {
                    $jsFile = $b;
                }
            }

            if (!$binFound || !$jsFile) {
                continue;
            }
            $data[$jsFile->getName()] = array('bitstream' => $jsFile->toArray());
            $data[$jsFile->getName()]['visible'] = true;
            $data[$jsFile->getName()]['red'] = 192 / 255;
            $data[$jsFile->getName()]['green'] = 192 / 255;
            $data[$jsFile->getName()]['blue'] = 195 / 255;
            $data[$jsFile->getName()]['hexa'] = $this->_rgb2hex((int) (192 * 255), (int) (192 * 255), (int) (192 * 255));
            $data[$jsFile->getName()]['name'] = "";
        }
        $this->view->data = JsonComponent::encode(array('webroot' => $this->view->webroot, 'objects' => $data));
    }

    /** convert color */
    private function _rgb2hex($r, $g = -1, $b = -1)
    {
        if (is_array($r) && count($r) == 3) {
            list($r, $g, $b) = $r;
        }

        $r = intval($r);
        $g = intval($g);
        $b = intval($b);

        $r = dechex($r < 0 ? 0 : ($r > 255 ? 255 : $r));
        $g = dechex($g < 0 ? 0 : ($g > 255 ? 255 : $g));
        $b = dechex($b < 0 ? 0 : ($b > 255 ? 255 : $b));

        $color = (strlen($r) < 2 ? '0' : '').$r;
        $color .= (strlen($g) < 2 ? '0' : '').$g;
        $color .= (strlen($b) < 2 ? '0' : '').$b;

        return $color;
    }
}
