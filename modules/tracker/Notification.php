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

require_once BASE_PATH.'/modules/api/library/APIEnabledNotification.php';

/** Notification manager for the tracker module */
class Tracker_Notification extends ApiEnabled_Notification
{
    public $moduleName = 'tracker';
    public $_models = array('User');
    public $_moduleModels = array('Scalar', 'Trend');
    public $_moduleComponents = array('Api');

    /** init notification process */
    public function init()
    {
        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->moduleWebroot = $baseUrl.'/'.$this->moduleName;
        $this->webroot = $baseUrl;

        $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_VIEW_TABS', 'communityViewTabs');
        $this->addCallBack('CALLBACK_CORE_GET_COMMUNITY_DELETED', 'communityDeleted');
        $this->addCallBack('CALLBACK_CORE_COMMUNITY_DELETED', 'communityDeleted');
        $this->addCallBack('CALLBACK_CORE_ITEM_DELETED', 'itemDeleted');
        $this->addCallBack('CALLBACK_CORE_USER_DELETED', 'userDeleted');

        $this->addTask('TASK_TRACKER_SEND_THRESHOLD_NOTIFICATION', 'sendEmail', 'Send threshold violation email');
        $this->addTask(
            'TASK_TRACKER_DELETE_TEMP_SCALAR',
            'deleteTempScalar',
            'Delete an unofficial/temporary scalar value'
        );
        $this->enableWebAPI($this->moduleName);
    }

    /**
     * Show trackers tab on the community view page
     */
    public function communityViewTabs($args)
    {
        $community = $args['community'];

        return array('Trackers' => $this->moduleWebroot.'/producer/list?communityId='.$community->getKey());
    }

    /**
     * When a community is deleted, we must delete all associated producers
     */
    public function communityDeleted($args)
    {
        // TODO
        // $comm = $args['community'];
    }

    /**
     * When an item is deleted, we must delete associated item2scalar records
     */
    public function itemDeleted($args)
    {
        // TODO
        // $item = $args['item'];
    }

    /**
     * When a user is deleted, we should delete their threshold notifications
     */
    public function userDeleted($args)
    {
        // TODO
        // $user = $args['userDao'];
    }

    /**
     * Delete temporary (unofficial) scalars after n hours, where n is specified as
     * a module configuration option
     */
    public function deleteTempScalar($params)
    {
        $scalarId = $params['scalarId'];
        $scalar = $this->Tracker_Scalar->load($scalarId);
        $this->Tracker_Scalar->delete($scalar);
    }

    /**
     * Send an email to the user that a threshold was crossed
     */
    public function sendEmail($params)
    {
        $notification = $params['notification'];
        $scalar = $params['scalar'];
        $trend = $this->Tracker_Trend->load($scalar['trend_id']);
        $user = $this->User->load($notification['recipient_id']);
        $producer = $trend->getProducer();

        if (!$user) {
            $this->getLogger()->warn(
                'Attempting to send threshold notification to user id '.$notification['recipient_id'].': No such user.'
            );

            return;
        }
        $baseUrl = UtilityComponent::getServerURL().$this->webroot;

        $email = $user->getEmail();
        $subject = 'Tracker Threshold Notification';
        $body = 'Hello,<br/><br/>This email was sent because a submitted scalar value violates a threshold that you specified.<br/><br/>';
        $body .= '<b>Community:</b> <a href="'.$baseUrl.'/community/'.$producer->getCommunityId(
            ).'">'.$producer->getCommunity()->getName().'</a><br/>';
        $body .= '<b>Producer:</b> <a href="'.$baseUrl.'/tracker/producer/view?producerId='.$producer->getKey(
            ).'">'.$producer->getDisplayName().'</a><br/>';
        $body .= '<b>Trend:</b> <a href="'.$baseUrl.'/tracker/trend/view?trendId='.$trend->getKey(
            ).'">'.$trend->getDisplayName().'</a><br/>';
        $body .= '<b>Value:</b> '.$scalar['value'];

        $result = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_SEND_MAIL_MESSAGE',
            array(
                'to' => $email,
                'subject' => $subject,
                'html' => $body,
                'event' => 'tracker_threshold_crossed',
            )
        );
    }
}
