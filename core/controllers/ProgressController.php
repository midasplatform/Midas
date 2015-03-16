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
 * Progress controller
 */
class ProgressController extends AppController
{
    public $_models = array('Progress');
    public $_daos = array();
    public $_components = array();
    public $_forms = array();

    /**
     * For any action that you intend to track progress on, you should call this first
     * to create and return your progress object.  You can then pass the progressId parameter
     * to the controller and it will update that progress object.
     *
     * @return Echoes the progress dao that was just created
     */
    public function createAction()
    {
        $this->disableLayout();
        $this->disableView();

        $progress = $this->Progress->createProgress();
        echo JsonComponent::encode($progress->toArray());
    }

    /**
     * Action for querying progress of a certain event. Should be called with ajax.
     *
     * @param progressId The id of the progress object to query
     * @return Echoes the progress dao if one exists, or false if it is completed or DNE.
     */
    public function getAction()
    {
        $progressId = $this->getParam('progressId');
        if (!isset($progressId)) {
            throw new Zend_Exception('Must pass progressId parameter');
        }
        $this->disableLayout();
        $this->disableView();

        $progress = $this->Progress->load($progressId);
        if (!$progress) {
            echo JsonComponent::encode(array());
        } else {
            echo JsonComponent::encode($progress->toArray());
        }
    }
}
