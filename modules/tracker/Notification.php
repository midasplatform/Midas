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

/**
 * Notification manager for the tracker module.
 *
 * @property Tracker_ScalarModel $Tracker_Scalar
 * @property Tracker_TrendModel $Tracker_Trend
 * @package Modules\Tracker\Notification
 */
class Tracker_Notification extends ApiEnabled_Notification
{
    /** @var string */
    public $moduleName = 'tracker';

    /** @var array */
    public $_models = array('User');

    /** @var array */
    public $_moduleModels = array('Scalar', 'Trend');

    /** @var array */
    public $_moduleComponents = array('Api');

    /** @var string */
    public $moduleWebroot = null;

    /** @var string */
    public $webroot = null;

    /** Initialize the notification process. */
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
     * Show the trackers tab on the community view page.
     *
     * @param array $args associative array of parameters including the key "community"
     * @return array
     */
    public function communityViewTabs($args)
    {
        /** @var CommunityDao $community */
        $community = $args['community'];

        return array('Trackers' => $this->moduleWebroot.'/producer/list?communityId='.$community->getKey());
    }

    /**
     * When a community is deleted, we must delete all associated producers.
     *
     * @todo
     * @param array $args associative array of parameters including the key "community"
     */
    public function communityDeleted($args)
    {
        // TODO: Implement communityDeleted().
    }

    /**
     * When an item is deleted, we must delete associated item2scalar records.
     *
     * @todo
     * @param array $args associative array of parameters including the key "item"
     */
    public function itemDeleted($args)
    {
        // TODO: Implement itemDeleted().
    }

    /**
     * When a user is deleted, we should delete their threshold notifications.
     *
     * @todo
     * @param array $args associative array of parameters including the key "userDao"
     */
    public function userDeleted($args)
    {
        // TODO: Implement userDeleted().
    }

    /**
     * Delete temporary (unofficial) scalars after n hours, where n is specified as
     * a module configuration option.
     *
     * @param array $params associative array of parameters including the key "scalarId"
     */
    public function deleteTempScalar($params)
    {
        /** @var int $scalarId */
        $scalarId = $params['scalarId'];

        /** @var Tracker_ScalarDao $scalar */
        $scalar = $this->Tracker_Scalar->load($scalarId);
        $this->Tracker_Scalar->delete($scalar);
    }

    /**
     * Send an email to the user that a threshold was crossed.
     *
     * @param array $params associative array of parameters including the keys "notification" and "scalar"
     */
    public function sendEmail($params)
    {
        /** @var array $notification */
        $notification = $params['notification'];

        /** @var array $scalar */
        $scalar = $params['scalar'];

        /** @var Tracker_TrendDao $trend */
        $trend = $this->Tracker_Trend->load($scalar['trend_id']);

        /** @var UserDao $user */
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

        Zend_Registry::get('notifier')->callback(
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
