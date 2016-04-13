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

require_once BASE_PATH.'/core/tests/controllers/api/RestCallMethodsTestCase.php';

/** API test for tracker module ApiaggregatemetricnotificationComponent. */
class Tracker_ApiAggregatemetricnotificationComponentTest extends RestCallMethodsTestCase
{
    public $moduleName = 'tracker';

    /** Setup. */
    public function setUp()
    {
        $this->enabledModules = array('api', 'scheduler', $this->moduleName);
        $this->_models = array('Assetstore', 'Community', 'Setting', 'User');
        $this->setupDatabase(array('default'));
        $this->setupDatabase(array('aggregateMetric'), 'tracker');

        ControllerTestCase::setUp();
    }

    /**
     * Test getting an existing existing aggregate metric notification with a set of params, via GET.
     *
     * @throws Zend_Exception
     */
    public function testGET()
    {
        $usersFile = $this->loadData('User', 'default');
        /** @var UserDao $userDao */
        $userDao = $this->User->load($usersFile[0]->getKey());
        $token = $this->_loginAsAdministrator();

        $restParams = array(
            'token' => $token,
        );
        $this->resetAll();
        $this->params = $restParams;
        $resp = $this->_callRestApi('GET', '/tracker/aggregatemetricnotification/1');

        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');
        /** @var Tracker_AggregateMetricNotificationDao $notificationDao */
        $notificationDao = $aggregateMetricNotificationModel->initDao('AggregateMetricNotification', json_decode(json_encode($resp['body']), true), $this->moduleName);

        $this->assertEquals($notificationDao->getAggregateMetricSpecId(), '1');
        $this->assertEquals($notificationDao->getBranch(), 'master');
        $this->assertEquals($notificationDao->getComparison(), '>');
        $this->assertEquals($notificationDao->getValue(), '19.0');
    }

    /**
     * Test creating an existing aggregate metric notification with a set of params, via POST.
     *
     * @throws Zend_Exception
     */
    public function testPOST()
    {
        $usersFile = $this->loadData('User', 'default');
        /** @var UserDao $userDao */
        $userDao = $this->User->load($usersFile[0]->getKey());
        $token = $this->_loginAsAdministrator();

        $restParams = array(
            'token' => $token,
            'aggregate_metric_spec_id' => 1,
            'branch' => 'POST TEST',
            'comparison' => '==',
            'value' => '16.0',
        );

        $this->resetAll();
        $this->params = $restParams;
        $resp = $this->_callRestApi('POST', '/tracker/aggregatemetricnotification/');

        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');
        /** @var Tracker_AggregateMetricNotificationDao $notificationDao */
        $notificationDao = $aggregateMetricNotificationModel->initDao('AggregateMetricNotification', json_decode(json_encode($resp['body']), true), $this->moduleName);

        // Test the result of the API call.
        $this->assertEquals($notificationDao->getAggregateMetricSpecId(), $restParams['aggregate_metric_spec_id']);
        $this->assertEquals($notificationDao->getValue(), $restParams['value']);
        $this->assertEquals($notificationDao->getComparison(), $restParams['comparison']);
        $this->assertEquals($notificationDao->getBranch(), $restParams['branch']);

        // Load from the DB and test again.
        $notificationDao = $aggregateMetricNotificationModel->load($notificationDao->getAggregateMetricNotificationId());

        $this->assertEquals($notificationDao->getAggregateMetricSpecId(), $restParams['aggregate_metric_spec_id']);
        $this->assertEquals($notificationDao->getValue(), $restParams['value']);
        $this->assertEquals($notificationDao->getComparison(), $restParams['comparison']);
        $this->assertEquals($notificationDao->getBranch(), $restParams['branch']);

        // Delete to clean up.
        $aggregateMetricNotificationModel->delete($notificationDao);
    }

    /**
     * Test updating an existing aggregate metric notification with a set of params, via PUT.
     *
     * @throws Zend_Exception
     */
    public function testPUT()
    {
        $usersFile = $this->loadData('User', 'default');
        /** @var UserDao $userDao */
        $userDao = $this->User->load($usersFile[0]->getKey());
        $token = $this->_loginAsAdministrator();

        $originalParams = array(
            'token' => $token,
            'aggregate_metric_spec_id' => 1,
            'branch' => 'master',
            'comparison' => '>',
            'value' => 19.0,
        );

        $restParams = array(
            'token' => $token,
            'aggregate_metric_spec_id' => 2,
            'branch' => 'retsam',
            'comparison' => '!=',
            'value' => 21.0,
        );

        $this->resetAll();
        $this->params = $restParams;
        $resp = $this->_callRestApi('PUT', '/tracker/aggregatemetricnotification/1');

        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');
        /** @var Tracker_AggregateMetricNotificationDao $notificationDao */
        $notificationDao = $aggregateMetricNotificationModel->initDao('AggregateMetricNotification', json_decode(json_encode($resp['body']), true), $this->moduleName);

        // Test the result of the API call.
        // The aggregate_metric_spec_id should not have changed.
        $this->assertEquals($notificationDao->getAggregateMetricSpecId(), $originalParams['aggregate_metric_spec_id']);
        $this->assertEquals($notificationDao->getValue(), $restParams['value']);
        $this->assertEquals($notificationDao->getComparison(), $restParams['comparison']);
        $this->assertEquals($notificationDao->getBranch(), $restParams['branch']);

        // Load from the DB and test again.
        $notificationDao = $aggregateMetricNotificationModel->load($notificationDao->getAggregateMetricNotificationId());

        // The aggregate_metric_spec_id should not have changed.
        $this->assertEquals($notificationDao->getAggregateMetricSpecId(), $originalParams['aggregate_metric_spec_id']);
        $this->assertEquals($notificationDao->getValue(), $restParams['value']);
        $this->assertEquals($notificationDao->getComparison(), $restParams['comparison']);
        $this->assertEquals($notificationDao->getBranch(), $restParams['branch']);

        // Reset via PUT to the original state.
        $this->resetAll();
        $this->params = $originalParams;
        $resp = $this->_callRestApi('PUT', '/tracker/aggregatemetricnotification/1');

        // Load from the DB and test again.
        $notificationDao = $aggregateMetricNotificationModel->load($notificationDao->getAggregateMetricNotificationId());

        $this->assertEquals($notificationDao->getAggregateMetricSpecId(), $originalParams['aggregate_metric_spec_id']);
        $this->assertEquals($notificationDao->getValue(), $originalParams['value']);
        $this->assertEquals($notificationDao->getComparison(), $originalParams['comparison']);
        $this->assertEquals($notificationDao->getBranch(), $originalParams['branch']);
    }

    /**
     * Test deleting an existing aggregate metric spec, via DELETE.
     *
     * @throws Zend_Exception
     */
    public function testDELETE()
    {
        $usersFile = $this->loadData('User', 'default');
        /** @var UserDao $userDao */
        $userDao = $this->User->load($usersFile[0]->getKey());
        $token = $this->_loginAsAdministrator();

        // Create a notification via POST.

        $restParams = array(
            'token' => $token,
            'aggregate_metric_spec_id' => 1,
            'branch' => 'DELETE TEST',
            'comparison' => '==',
            'value' => '16.0',
        );

        $this->resetAll();
        $this->params = $restParams;
        $resp = $this->_callRestApi('POST', '/tracker/aggregatemetricnotification/');

        /** @var Tracker_AggregateMetricNotificationModel $aggregateMetricNotificationModel */
        $aggregateMetricNotificationModel = MidasLoader::loadModel('AggregateMetricNotification', 'tracker');
        /** @var Tracker_AggregateMetricNotificationDao $notificationDao */
        $notificationDao = $aggregateMetricNotificationModel->initDao('AggregateMetricNotification', json_decode(json_encode($resp['body']), true), $this->moduleName);

        // Load from the DB and test properties.
        $notificationDao = $aggregateMetricNotificationModel->load($notificationDao->getAggregateMetricNotificationId());
        $this->assertEquals($notificationDao->getAggregateMetricSpecId(), $restParams['aggregate_metric_spec_id']);
        $this->assertEquals($notificationDao->getValue(), $restParams['value']);
        $this->assertEquals($notificationDao->getComparison(), $restParams['comparison']);
        $this->assertEquals($notificationDao->getBranch(), $restParams['branch']);

        $notificationId = $notificationDao->getAggregateMetricNotificationId();
        $resp = $this->_callRestApi('DELETE', '/tracker/aggregatemetricnotification/'.$notificationId);

        $notificationDao = $aggregateMetricNotificationModel->load($notificationId);
        $this->assertEquals($notificationDao, false);
    }
}
