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
class Pvw_ParaviewController extends Pvw_AppController
{
    public $_models = array('Item', 'ItemRevision', 'Bitstream', 'Setting');
    public $_moduleComponents = array('Paraview');
    public $_moduleModels = array('Instance');

    /**
     * This action should be invoked via XMLHttpRequest
     * to start a paraview web instance. Relevant info will
     * be returned to the client.
     *
     * @param [appname] The name of the application to start, defaults to midas.
     * All available apps live in the 'apps' directory of this module.
     * @param itemId The id of the item to be rendered. Item data will be symlinked into the
     * location expected by pvpython
     * @param [meshes] List of item id's representing meshes to display in the scene, separated by ;
     * @return The info needed by the client to connect to the session
     */
    public function startinstanceAction()
    {
        UtilityComponent::setTimeLimit(30);
        $this->disableView();
        $this->disableLayout();

        try {
            $appname = $this->getParam('appname');
            if (!isset($appname)) {
                $appname = 'midas';
            }
            $itemId = $this->getParam('itemId');
            if (!isset($itemId)) {
                throw new Zend_Exception('Must pass an itemId', 400);
            }
            $item = $this->Item->load($itemId);
            if (!$item) {
                throw new Zend_Exception('Invalid itemId', 404);
            }
            if (!$this->Item->policyCheck($item, $this->userSession->Dao)) {
                throw new Zend_Exception('Read access required on item', 403);
            }

            $meshes = $this->getParam('meshes');
            if (isset($meshes)) {
                $meshes = explode(';', $meshes);
            } else {
                $meshes = array();
            }
            $meshItems = array();
            foreach ($meshes as $meshId) {
                if (!$meshId) {
                    continue;
                }
                $meshItem = $this->Item->load($meshId);
                if (!$this->Item->policyCheck($meshItem, $this->userSession->Dao)
                ) {
                    throw new Zend_Exception('Read access required on mesh item '.$meshId, 403);
                }
                $meshItems[] = $meshItem;
            }

            $instance = $this->ModuleComponent->Paraview->createAndStartInstance(
                $item,
                $meshItems,
                $appname,
                $this->progressDao
            );

            echo JsonComponent::encode(array('status' => 'ok', 'instance' => $instance->toArray()));
        } catch (Exception $e) {
            echo JsonComponent::encode(array('status' => 'error', 'message' => nl2br($e->getMessage())));
        }
    }

    /**
     * This should become a RESTful controller for instances
     */
    public function instanceAction()
    {
        UtilityComponent::setTimeLimit(30); // in case an exec call hangs for some odd reason
        // TODO just plug this into the RESTful stuff
        $this->disableLayout();
        $this->disableView();
        $pathParams = UtilityComponent::extractPathParams();
        if (empty($pathParams)) {
            throw new Zend_Exception('Must pass instance id as first path parameter', 400);
        }
        $instanceDao = $this->Pvw_Instance->load($pathParams[0]);
        if (!$instanceDao) {
            throw new Zend_Exception('Invalid instance id: '.$pathParams[0], 400);
        }
        $this->ModuleComponent->Paraview->killInstance($instanceDao);
        echo JsonComponent::encode(array('status' => 'ok', 'message' => 'Instance destroyed'));
    }

    /**
     * Surface (mesh) model viewer action
     *
     * @param itemId The id of the item to view
     */
    public function surfaceAction()
    {
        $itemId = $this->getParam('itemId');
        if (!isset($itemId)) {
            throw new Zend_Exception('Must pass itemId param', 400);
        }
        $item = $this->Item->load($itemId);
        if (!$item) {
            throw new Zend_Exception('Invalid itemId', 404);
        }
        if (!$this->Item->policyCheck($item, $this->userSession->Dao)) {
            throw new Zend_Exception('Read permission required', 403);
        }

        $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/pqUnstructuredGrid16.png" />';
        $header .= ' Surface view: <a href="'.$this->view->webroot.'/item/'.$item->getKey().'">'.$item->getName(
            ).'</a>';
        $this->view->header = $header;
        $this->view->json['pvw']['item'] = $item;
        $this->view->json['pvw']['viewMode'] = 'surface';
        $this->view->item = $item;
    }

    /**
     * Display a volume rendering of the selected item
     *
     * @param itemId The id of the item to visualize
     * @param jsImports (Optional) List of javascript files to import. These should contain handler
     *                             functions for imported operations. Separated by ;
     * @param meshes (Optional) List of item id's corresponding to surface meshes to visualize in the scene
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

        $itemId = $this->getParam('itemId');
        if (!isset($itemId)) {
            throw new Zend_Exception('Must pass itemId param', 400);
        }
        $item = $this->Item->load($itemId);
        if (!$item) {
            throw new Zend_Exception('Invalid itemId', 404);
        }
        if (!$this->Item->policyCheck($item, $this->userSession->Dao)) {
            throw new Zend_Exception('Read permission required', 403);
        }

        $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/volume.png" />';
        $header .= ' Volume rendering: <a href="'.$this->view->webroot.'/item/'.$item->getKey().'">'.$item->getName(
            ).'</a>';
        $this->view->header = $header;
        $this->view->json['pvw']['item'] = $item;
        $this->view->json['pvw']['meshIds'] = $meshes;
        $this->view->json['pvw']['viewMode'] = 'volume';
        $this->view->item = $item;
    }

    /**
     * Use the axial slice view mode for MetaImage volume data
     *
     * @param itemId The id of the MetaImage item to visualize
     * @param jsImports (Optional) List of javascript files to import. These should contain handler
     *                             functions for imported operations. Separated by ;
     * @param meshes (Optional) List of item id's corresponding to surface meshes to visualize in the scene
     * @param operations (Optional) List of operations separated by ; to allow
     */
    public function sliceAction()
    {
        $jsImports = $this->getParam('jsImports');
        $operations = $this->getParam('operations');
        if (isset($jsImports)) {
            $this->view->jsImports = explode(';', $jsImports);
        } else {
            $this->view->jsImports = array();
        }
        if (isset($operations)) {
            $operations = explode(';', $operations);
        } else {
            $operations = array();
        }

        $itemId = $this->getParam('itemId');
        if (!isset($itemId)) {
            throw new Zend_Exception('Must pass itemId param', 400);
        }
        $item = $this->Item->load($itemId);
        if (!$item) {
            throw new Zend_Exception('Invalid itemId', 404);
        }
        if (!$this->Item->policyCheck($item, $this->userSession->Dao)) {
            throw new Zend_Exception('Read permission required', 403);
        }

        $meshes = $this->getParam('meshes');
        if (isset($meshes)) {
            $meshes = explode(';', $meshes);
        } else {
            $meshes = array();
        }

        $header = '<img style="position: relative; top: 3px;" alt="" src="'.$this->view->moduleWebroot.'/public/images/sliceView.png" />';
        $header .= ' Slice view: <a href="'.$this->view->webroot.'/item/'.$item->getKey().'">'.$item->getName().'</a>';
        $this->view->header = $header;
        $this->view->json['pvw']['item'] = $item;
        $this->view->json['pvw']['meshIds'] = $meshes;
        $this->view->json['pvw']['viewMode'] = 'slice';
        $this->view->json['pvw']['operations'] = $operations;
        $this->view->item = $item;
    }

    /**
     * Connect to an already running PVW session
     *
     * @param instanceId The id of the PVW instance
     * @param authKey The authorization key of the instance
     */
    public function shareAction()
    {
        $instanceId = $this->getParam('instanceId');
        $authKey = $this->getParam('authKey');
        if (!isset($instanceId)) {
            throw new Zend_Exception('Must pass instanceId param', 400);
        }
        if (!isset($authKey)) {
            throw new Zend_Exception('Must pass authKey param', 400);
        }
        $instanceDao = $this->Pvw_Instance->load($instanceId);
        if (!$instanceDao) {
            throw new Zend_Exception('That instance no longer exists', 400);
        }
        if ($instanceDao->getSecret() !== $authKey) {
            throw new Zend_Exception('Invalid authentication key', 403);
        }
        $this->view->json['pvw']['instance'] = $instanceDao;
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
}
