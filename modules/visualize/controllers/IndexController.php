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

/** Index controller */
class Visualize_IndexController extends Visualize_AppController
{
    public $_moduleComponents = array('Main');
    public $_models = array('Item');

    /** index */
    public function indexAction()
    {
        $height = $this->getParam('height');
        $width = $this->getParam('width');
        $viewMode = $this->getParam('viewMode');
        if (!isset($viewMode)) {
            $viewMode = 'volume';
        }
        if (!isset($height)) {
            $height = 500;
        }
        if (!isset($width)) {
            $width = 500;
        }
        $itemId = $this->getParam('itemId');
        $itemDao = $this->Item->load($itemId);

        if ($this->ModuleComponent->Main->canVisualizeWithParaview($itemDao)) {
            if ($viewMode == 'slice') {
                $this->redirect(
                    '/visualize/paraview/slice?itemId='.$itemId.'&height='.$height.'&width='.$width
                );
            } else { // normal volume rendering
                $this->redirect('/visualize/paraview/?itemId='.$itemId.'&height='.$height.'&width='.$width);
            }
        } elseif ($this->ModuleComponent->Main->canVisualizeMedia($itemDao)) {
            $this->redirect('/visualize/media/?itemId='.$itemId.'&height='.$height.'&width='.$width);
        } elseif ($this->ModuleComponent->Main->canVisualizeTxt($itemDao)) {
            $this->redirect('/visualize/txt/?itemId='.$itemId.'&height='.$height.'&width='.$width);
        } elseif ($this->ModuleComponent->Main->canVisualizeImage($itemDao)) {
            $this->redirect('/visualize/image/?itemId='.$itemId.'&height='.$height.'&width='.$width);
        } elseif ($this->ModuleComponent->Main->canVisualizePdf($itemDao)) {
            $this->redirect('/visualize/pdf/?itemId='.$itemId.'&height='.$height.'&width='.$width);
        } elseif ($this->ModuleComponent->Main->canVisualizeWebgl($itemDao)) {
            $this->redirect('/visualize/webgl/?itemId='.$itemId.'&height='.$height.'&width='.$width);
        } else {
            throw new Zend_Exception('Unable to visualize');
        }
    }
}
