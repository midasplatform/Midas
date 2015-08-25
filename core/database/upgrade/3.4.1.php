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

/** Upgrade the core to version 3.4.1. */
class Upgrade_3_4_1 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `module` (
                `module_id` bigint(20) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `uuid` varchar(36) NOT NULL,
                `current_major_version` int(11) NOT NULL DEFAULT '0',
                `current_minor_version` int(11) NOT NULL DEFAULT '0',
                `current_patch_version` int(11) NOT NULL DEFAULT '0',
                `enabled` tinyint(4) NOT NULL DEFAULT '0',
                PRIMARY KEY (`module_id`),
                UNIQUE KEY (`name`),
                UNIQUE KEY (`uuid`)
            ) DEFAULT CHARSET=utf8;
        ");
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query('
            CREATE TABLE IF NOT EXISTS "module" (
                "module_id" serial PRIMARY KEY,
                "name" character varying(256) NOT NULL,
                "uuid" character varying(36) NOT NULL,
                "current_major_version" integer NOT NULL DEFAULT 0,
                "current_minor_version" integer NOT NULL DEFAULT 0,
                "current_patch_version" integer NOT NULL DEFAULT 0,
                "enabled" smallint NOT NULL DEFAULT 0
            );
        ');
        $this->db->query('CREATE UNIQUE INDEX "module_idx_name" ON "module" ("name");');
        $this->db->query('CREATE UNIQUE INDEX "module_idx_uuid" ON "module" ("uuid");');
    }

    /** Upgrade a SQLite database. */
    public function sqlite()
    {
        $this->db->query('
            CREATE TABLE IF NOT EXISTS "module" (
                "module_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                "name" TEXT NOT NULL,
                "uuid" TEXT NOT NULL,
                "current_major_version" INTEGER NOT NULL DEFAULT 0,
                "current_minor_version" INTEGER NOT NULL DEFAULT 0,
                "current_patch_version" INTEGER NOT NULL DEFAULT 0,
                "enabled" INTEGER NOT NULL DEFAULT 0
            );
        ');
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS "module_name_idx" ON "module" ("name");');
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS "module_uuid_idx" ON "module" ("uuid");');
    }

    /** Post database upgrade. */
    public function postUpgrade()
    {
        /** @var ModuleModel $moduleModel */
        $moduleModel = MidasLoader::loadModel('Module');

        /** @var UuidComponent $uuidComponent */
        $uuidComponent = MidasLoader::loadComponent('Uuid');

        $modules = new Zend_Config_Ini(APPLICATION_CONFIG, 'module');
        $oldLocalConfigs = array();

        foreach ($modules as $key => $value) {
            if (file_exists(BASE_PATH.'/modules/'.$key.'/AppController.php')) {
                $moduleRoot = BASE_PATH.'/modules/'.$key;
            } elseif (file_exists(BASE_PATH.'/privateModules/'.$key.'/AppController.php')) {
                $moduleRoot = BASE_PATH.'/privateModules/'.$key;
            } else {
                continue;
            }

            $moduleConfig = new Zend_Config_Ini($moduleRoot.'/configs/module.ini', 'global');

            /** @var ModuleDao $moduleDao */
            $moduleDao = MidasLoader::newDao('ModuleDao');
            $moduleDao->setName($key);

            $uuid = $moduleConfig->get('uuid', false);

            if ($uuid === false) {
                $moduleDao->setUuid($uuidComponent->generate());
            } else {
                $moduleDao->setUuid(str_replace('-', '', $uuid));
            }

            $localConfigPath = LOCAL_CONFIGS_PATH.'/'.$key.'.local.ini';

            if (file_exists($localConfigPath)) {
                $localConfig = new Zend_Config_Ini($localConfigPath, 'global');
                $moduleDao->setCurrentVersion($localConfig->get('version', '0.0.0'));
            } else {
                $localConfig = false;
                $moduleDao->setCurrentVersion($moduleConfig->get('version', '0.0.0'));
            }

            $moduleDao->setEnabled($value == 1 ? 1 : 0);
            $moduleModel->save($moduleDao);

            if ($uuid && $localConfig) {
                $oldLocalConfigs[] = $localConfigPath;
            }
        }

        $applicationConfig = new Zend_Config_Ini(APPLICATION_CONFIG, null, true);
        unset($applicationConfig->module);

        $applicationWriter = new Zend_Config_Writer_Ini();
        $applicationWriter->setConfig($applicationConfig);
        $applicationWriter->setFilename(APPLICATION_CONFIG);
        $applicationWriter->write();

        $databaseConfig = new Zend_Config_Ini(DATABASE_CONFIG, null, true);
        unset($databaseConfig->development->version);
        unset($databaseConfig->production->version);
        unset($databaseConfig->testing->version);

        $databaseWriter = new Zend_Config_Writer_Ini();
        $databaseWriter->setConfig($databaseConfig);
        $databaseWriter->setFilename(DATABASE_CONFIG);
        $databaseWriter->write();

        unset(Zend_Registry::get('configDatabase')->version);

        /** @var string $oldLocalConfig */
        foreach ($oldLocalConfigs as $oldLocalConfig) {
            rename($oldLocalConfig, str_replace('.local.ini', '.old.local.ini', $oldLocalConfig));
        }
    }
}
