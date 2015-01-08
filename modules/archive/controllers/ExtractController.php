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

/** Main controller for the web api module */
class Archive_ExtractController extends Archive_AppController
{
    public $_models = array('Item', 'Progress');
    public $_moduleComponents = array('Extract');

    /**
     * Show the extract archive dialog
     *
     * @param itemId The item in question
     */
    public function dialogAction()
    {
        $this->disableLayout();
        $this->view->itemId = $this->getParam('itemId');
    }

    /**
     * Perform the extraction. Requires admin privileges on the item.
     *
     * @param itemId The item to extract
     * @throws Zend_Exception
     */
    public function performAction()
    {
        $this->disableView();
        $this->disableLayout();
        $itemId = $this->getParam('itemId');
        $deleteArchive = $this->getParam('deleteArchive');
        $deleteArchive = $deleteArchive !== 'false'; // passed as a string

        if (!isset($itemId)) {
            throw new Zend_Exception('Must pass itemId parameter');
        }
        $item = $this->Item->load($itemId);
        if (!$item) {
            throw new Zend_Exception('Invalid itemId');
        }
        if (!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_ADMIN)
        ) {
            throw new Zend_Exception('Admin privileges on item required');
        }

        try {
            $parentFolder = $this->ModuleComponent->Extract->extractInPlace(
                $item,
                $deleteArchive,
                $this->userSession->Dao,
                $this->progressDao
            );

            echo JsonComponent::encode(
                array('status' => 'ok', 'redirect' => $this->view->webroot.'/folder/'.$parentFolder->getKey())
            );
        } catch (Exception $e) {
            echo JsonComponent::encode(array('status' => 'error', 'message' => $e->getMessage()));
        }
    }
}
