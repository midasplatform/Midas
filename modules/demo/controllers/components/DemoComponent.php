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

/** Demo component for the demo module */
class Demo_DemoComponent extends AppComponent
{
    public $moduleName = 'demo';

    /** Reset database (only works with MySQL) */
    public function reset()
    {
        $db = Zend_Registry::get('dbAdapter');
        $dbname = Zend_Registry::get('configDatabase')->database->params->dbname;

        $stmt = $db->query("SELECT * FROM INFORMATION_SCHEMA.TABLES where TABLE_SCHEMA = '".$dbname."'");
        while ($row = $stmt->fetch()) {
            $db->query("DELETE FROM `".$row['TABLE_NAME']."`");
        }

        $path = UtilityComponent::getDataDirectory('assetstore');
        $dir = opendir($path);
        while ($entry = readdir($dir)) {
            if (is_dir($path.'/'.$entry) && !in_array($entry, array('.', '..'))
            ) {
                $this->_rrmdir($path.'/'.$entry);
            }
        }

        $path = UtilityComponent::getDataDirectory('thumbnail');
        $dir = opendir($path);
        while ($entry = readdir($dir)) {
            if (is_dir($path.'/'.$entry) && !in_array($entry, array('.', '..'))
            ) {
                $this->_rrmdir($path.'/'.$entry);
            }
        }

        if (file_exists(LOCAL_CONFIGS_PATH.'/ldap.local.ini')) {
            unlink(LOCAL_CONFIGS_PATH.'/ldap.local.ini');
        }

        $userModel = MidasLoader::loadModel('User');

        $admin = $userModel->createUser(MIDAS_DEMO_ADMIN_EMAIL, MIDAS_DEMO_ADMIN_PASSWORD, 'Demo', 'Administrator', 1);
        $userModel->createUser(MIDAS_DEMO_USER_EMAIL, MIDAS_DEMO_USER_PASSWORD, 'Demo', 'User', 0);

        $communityModel = MidasLoader::loadModel('Community');
        $communityDao = $communityModel->createCommunity(
            'Demo',
            'This is a demo community',
            MIDAS_COMMUNITY_PUBLIC,
            $admin,
            MIDAS_COMMUNITY_CAN_JOIN
        );

        $assetstoreModel = MidasLoader::loadModel('Assetstore');
        $assetstoreDao = new AssetstoreDao();
        $assetstoreDao->setName('Default');
        $assetstoreDao->setPath(UtilityComponent::getDataDirectory('assetstore'));
        $assetstoreDao->setType(MIDAS_ASSETSTORE_LOCAL);
        $assetstoreModel->save($assetstoreDao);

        $options = array('allowModifications' => true);
        $config = new Zend_Config_Ini(CORE_CONFIGS_PATH.'/application.ini', null, $options);
        $config->global->defaultassetstore->id = $assetstoreDao->getKey();
        $config->global->dynamichelp = 1;
        $config->global->environment = 'production';
        $config->global->application->name = 'Midas Platform - Demo';
        $description = 'Midas Platform is an open-source toolkit that enables the
      rapid creation of tailored, web-enabled data storage. Designed to meet
      the needs of advanced data-centric computing, Midas Platform addresses
      the growing challenge of large data by providing a flexible, intelligent
      data storage system. The system integrates multimedia server technology
      with other open-source data analysis and visualization tools to enable
      data-intensive applications that easily interface with existing
      workflows.';
        $config->global->application->description = $description;

        $enabledModules = array(
            'api',
            'metadataextractor',
            'oai',
            'statistics',
            'scheduler',
            'thumbnailcreator',
            'visualize',
        );
        foreach ($enabledModules as $module) {
            if (file_exists(LOCAL_CONFIGS_PATH.'/'.$module.'.demo.local.ini')) {
                copy(
                    LOCAL_CONFIGS_PATH.'/'.$module.'.demo.local.ini',
                    LOCAL_CONFIGS_PATH.'/'.$module.'.local.ini'
                );
                $config->module->$module = 1;
            } else {
                unlink(LOCAL_CONFIGS_PATH.'/'.$module.'.local.ini');
            }
        }

        $writer = new Zend_Config_Writer_Ini();
        $writer->setConfig($config);
        $writer->setFilename((LOCAL_CONFIGS_PATH.'/application.local.ini'));
        $writer->write();

        $configGlobal = new Zend_Config_Ini(APPLICATION_CONFIG, 'global', true);
        Zend_Registry::set('configGlobal', $configGlobal);

        $uploadComponent = MidasLoader::loadComponent('Upload');
        $uploadComponent->createUploadedItem(
            $admin,
            'midasLogo.gif',
            BASE_PATH.'/core/public/images/midasLogo.gif',
            $communityDao->getPublicFolder(),
            null,
            '',
            true
        );
        $uploadComponent->createUploadedItem(
            $admin,
            'cow.vtp',
            BASE_PATH.'/modules/demo/public/'.$this->moduleName.'/cow.vtp',
            $communityDao->getPublicFolder(),
            null,
            '',
            true
        );
    }

    /** Recursively delete a folder */
    private function _rrmdir($dir)
    {
        if (!file_exists($dir)) {
            return;
        }
        if (is_dir($dir)) {
            $objects = scandir($dir);
        }

        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (filetype($dir.'/'.$object) == 'dir') {
                    $this->_rrmdir($dir.'/'.$object);
                } else {
                    unlink($dir.'/'.$object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}
