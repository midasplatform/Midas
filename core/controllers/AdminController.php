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

/**
 * Admin Controller
 */
class AdminController extends AppController
{
    public $_models = array('Assetstore', 'Bitstream', 'Errorlog', 'Item', 'ItemRevision', 'Folder', 'License');
    public $_daos = array();
    public $_components = array('Upgrade', 'Utility', 'MIDAS2Migration');
    public $_forms = array('Admin', 'Assetstore', 'Migrate');

    /** init the controller */
    public function init()
    {
        $config = Zend_Registry::get('configGlobal'); // set admin part to english
        $config->application->lang = 'en';
        Zend_Registry::set('configGlobal', $config);
        if ($this->isDemoMode()) {
            $this->disableView();

            return false;
        }
    }

    /** run a task */
    public function taskAction()
    {
        $this->requireAdminPrivileges();

        $task = $this->getParam("task");
        $params = $this->getParam("params");
        if (isset($params)) {
            $params = JsonComponent::decode($params);
        } else {
            $params = array();
        }

        if ($this->progressDao) {
            $params['progressDao'] = $this->progressDao;
        }

        $modules = Zend_Registry::get('notifier')->modules;
        $tasks = Zend_Registry::get('notifier')->tasks;
        call_user_func(array($modules[$tasks[$task]['module']], $tasks[$task]['method']), $params);
        $this->disableLayout();
        $this->disableView();
    }

    /** index */
    public function indexAction()
    {
        $this->requireAdminPrivileges();
        $this->view->header = "Administration";

        $options = array('allowModifications' => true);
        $config = new Zend_Config_Ini(APPLICATION_CONFIG, null, $options);

        $configForm = $this->Form->Admin->createConfigForm();
        $formArray = $this->getFormAsArray($configForm);
        $formArray['description']->setValue($config->global->application->description);
        $formArray['lang']->setValue($config->global->application->lang);
        $formArray['name']->setValue($config->global->application->name);
        $formArray['timezone']->setValue($config->global->default->timezone);

        if (isset($config->global->closeregistration)) {
            $formArray['closeregistration']->setValue($config->global->closeregistration);
        }
        if (isset($config->global->dynamichelp)) {
            $formArray['dynamichelp']->setValue($config->global->dynamichelp);
        }
        if (isset($config->global->gravatar)) {
            $formArray['gravatar']->setValue($config->global->gravatar);
        }
        if (isset($config->global->httpproxy)) {
            $formArray['httpProxy']->setValue($config->global->httpproxy);
        }
        if (isset($config->global->logtrace)) {
            $formArray['logtrace']->setValue($config->global->logtrace);
        }
        $this->view->configForm = $formArray;

        $this->view->selectedLicense = $config->global->defaultlicense;
        try {
            $this->view->allLicenses = $this->License->getAll();
        } catch (Exception $e) {
            $this->view->allLicenses = array();
        }

        $allModules = $this->Component->Utility->getAllModules();
        $this->view->extraTabs = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_ADMIN_TABS');

        if ($this->_request->isPost()) {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender();
            $submitConfig = $this->getParam('submitConfig');
            $submitModule = $this->getParam('submitModule');
            if (isset($submitConfig)) {
                $config->global->application->name = $this->getParam('name');
                $config->global->application->description = $this->getParam('description');
                $config->global->application->lang = $this->getParam('lang');
                $config->global->environment = $this->getParam('environment');
                $config->global->default->timezone = $this->getParam('timezone');
                $config->global->defaultlicense = $this->getParam('licenseSelect');
                $config->global->dynamichelp = $this->getParam('dynamichelp');
                $config->global->closeregistration = $this->getParam('closeregistration');
                $config->global->logtrace = $this->getParam('logtrace');
                $config->global->httpproxy = $this->getParam('httpProxy');
                $config->global->gravatar = $this->getParam('gravatar');

                $writer = new Zend_Config_Writer_Ini();
                $writer->setConfig($config);
                $writer->setFilename(APPLICATION_CONFIG);
                $writer->write();
                echo JsonComponent::encode(array(true, 'Changes saved'));
            }
            if (isset($submitModule)) {
                $moduleName = $this->getParam('modulename');
                $modulevalue = $this->getParam('modulevalue');
                $moduleConfigLocalFile = LOCAL_CONFIGS_PATH."/".$moduleName.".local.ini";
                $moduleConfigFile = BASE_PATH."/modules/".$moduleName."/configs/module.ini";
                $moduleConfigPrivateFile = BASE_PATH."/privateModules/".$moduleName."/configs/module.ini";
                if (!file_exists($moduleConfigLocalFile) && file_exists($moduleConfigFile)
                ) {
                    copy($moduleConfigFile, $moduleConfigLocalFile);
                    $this->Component->Utility->installModule($moduleName);
                } elseif (!file_exists($moduleConfigLocalFile) && file_exists($moduleConfigPrivateFile)
                ) {
                    copy($moduleConfigPrivateFile, $moduleConfigLocalFile);
                    $this->Component->Utility->installModule($moduleName);
                } elseif (!file_exists($moduleConfigLocalFile)) {
                    throw new Zend_Exception("Unable to find config file");
                }

                $config->module->$moduleName = $modulevalue;

                $writer = new Zend_Config_Writer_Ini();
                $writer->setConfig($config);
                $writer->setFilename(APPLICATION_CONFIG);
                $writer->write();
                echo JsonComponent::encode(array(true, 'Changes saved'));
            }
        }

        $defaultAssetStoreId = $this->Assetstore->getDefault()->getKey();

        // get assetstore data
        $assetstores = $this->Assetstore->getAll();
        $defaultSet = false;
        foreach ($assetstores as $key => $assetstore) {
            if ($assetstore->getKey() == $defaultAssetStoreId) {
                $assetstores[$key]->default = true;
                $defaultSet = true;
            } else {
                $assetstores[$key]->default = false;
            }

            // Check if we can access the path
            if (file_exists($assetstore->getPath())) {
                $assetstores[$key]->totalSpace = UtilityComponent::diskTotalSpace($assetstore->getPath());
                $assetstores[$key]->totalSpaceText = $this->Component->Utility->formatSize(
                    $assetstores[$key]->totalSpace
                );
                $assetstores[$key]->freeSpace = UtilityComponent::diskFreeSpace($assetstore->getPath());
                $assetstores[$key]->freeSpaceText = $this->Component->Utility->formatSize(
                    $assetstores[$key]->freeSpace
                );
            } else {
                $assetstores[$key]->totalSpaceText = false;
            }
        }

        if (!$defaultSet) {
            foreach ($assetstores as $key => $assetstore) {
                $assetstores[$key]->default = true;
                $config->global->defaultassetstore->id = $assetstores[$key]->getKey();

                $writer = new Zend_Config_Writer_Ini();
                $writer->setConfig($config);
                $writer->setFilename(APPLICATION_CONFIG);
                $writer->write();
                break;
            }
        }
        $this->view->assetstores = $assetstores;
        $this->view->assetstoreForm = $this->Form->Assetstore->createAssetstoreForm();

        // get modules
        $modulesEnable = Zend_Registry::get('modulesEnable');
        $adapter = Zend_Registry::get('configDatabase')->database->adapter;
        foreach ($allModules as $key => $module) {
            if (file_exists(BASE_PATH."/modules/".$key."/controllers/ConfigController.php")) {
                $allModules[$key]->configPage = 'config';
            } elseif (file_exists(BASE_PATH."/privateModules/".$key."/controllers/ConfigController.php")) {
                $allModules[$key]->configPage = 'config';
            } elseif (file_exists(BASE_PATH."/modules/".$key."/controllers/AdminController.php")) {
                $allModules[$key]->configPage = 'admin';
            } elseif (file_exists(BASE_PATH."/privateModules/".$key."/controllers/AdminController.php")) {
                $allModules[$key]->configPage = 'admin';
            } else {
                $allModules[$key]->configPage = false;
            }

            if (isset($module->db->$adapter)) {
                $allModules[$key]->dbOk = true;
            } else {
                $allModules[$key]->dbOk = false;
            }

            $allModules[$key]->dependenciesArray = array();
            $allModules[$key]->dependenciesExist = true;
            // check if dependencies exit
            if (isset($allModules[$key]->dependencies) && !empty($allModules[$key]->dependencies)) {
                $allModules[$key]->dependenciesArray = explode(',', trim($allModules[$key]->dependencies));
                foreach ($allModules[$key]->dependenciesArray as $dependency) {
                    if (!isset($allModules[$dependency])) {
                        $allModules[$key]->dependenciesExist = false;
                    }
                }
            }
        }

        $modulesList = array();
        $countModules = array();
        foreach ($allModules as $k => $module) {
            if (!isset($module->category) || empty($module->category)) {
                $category = "Misc";
            } else {
                $category = ucfirst($module->category);
            }
            if (!isset($modulesList[$category])) {
                $modulesList[$category] = array();
                $countModules[$category] = array('visible' => 0, 'hidden' => 0);
            }
            $modulesList[$category][$k] = $module;
            if ($module->dbOk && $module->dependenciesExist) {
                $countModules[$category]['visible']++;
            } else {
                $countModules[$category]['hidden']++;
            }
        }

        foreach ($modulesList as $k => $l) {
            ksort($modulesList[$k]);
        }

        ksort($modulesList);
        $this->view->countModules = $countModules;
        $this->view->modulesList = $modulesList;
        $this->view->modulesEnable = $modulesEnable;
        $this->view->databaseType = Zend_Registry::get('configDatabase')->database->adapter;
    }

    /**
     * Used to display and filter the list of log messages
     *
     * @param startlog The start date to filter log entries by
     * @param endlog The end date to filter log entries by
     * @param modulelog What module to filter by
     * @param prioritylog Priority to filter by
     * @param priorityOperator Priority operator ('==' | '<=')
     * @param limit Page limit
     * @param offset Page offset
     */
    public function showlogAction()
    {
        $this->requireAdminPrivileges();
        $this->disableLayout();

        $start = $this->getParam('startlog');
        $end = $this->getParam('endlog');
        $module = $this->getParam('modulelog');
        $priority = $this->getParam('prioritylog');
        $priorityOperator = $this->getParam('priorityOperator');
        $limit = $this->getParam('limit');
        $offset = $this->getParam('offset');
        if (!isset($start) || empty($start)) {
            $start = date('Y-m-d H:i:s', strtotime('-24 hour'));
        } else {
            $start = date('Y-m-d H:i:s', strtotime($start));
        }
        if (!isset($end) || empty($end)) {
            $end = date('Y-m-d H:i:s');
        } else {
            $end = date('Y-m-d H:i:s', strtotime($end));
        }
        if (!isset($module) || empty($module)) {
            $module = 'all';
        }
        if (!isset($priority) || empty($priority)) {
            $priority = MIDAS_PRIORITY_WARNING;
        }
        if (!isset($priorityOperator) || empty($priorityOperator)) {
            $priorityOperator = '<=';
        }
        if (!isset($limit) || empty($limit)) {
            $limit = 100;
        }
        if (!isset($offset) || empty($offset)) {
            $offset = 0;
        }

        $results = $this->Errorlog->getLog($start, $end, $module, $priority, $limit, $offset, $priorityOperator);
        $this->view->jsonContent = array();
        $this->view->jsonContent['currentFilter'] = array(
            'start' => $start,
            'end' => $end,
            'module' => $module,
            'priority' => $priority,
            'priorityOperator' => $priorityOperator,
            'limit' => $limit,
            'offset' => $offset,
        );
        $logs = $results['logs'];
        foreach ($logs as $key => $log) {
            $logs[$key] = $log->toArray();
            if (substr($log->getMessage(), 0, 5) == 'Fatal') {
                $shortMessage = substr($log->getMessage(), strpos($log->getMessage(), '[message]') + 13, 60);
            } elseif (substr($log->getMessage(), 0, 6) == 'Server') {
                $shortMessage = substr($log->getMessage(), strpos($log->getMessage(), 'Message:') + 9, 60);
            } else {
                $shortMessage = substr($log->getMessage(), 0, 60);
            }
            $logs[$key]['shortMessage'] = $shortMessage.' ...';
        }

        $this->view->jsonContent['logs'] = $logs;
        $this->view->jsonContent['total'] = $results['total'];

        if ($this->_request->isPost()) {
            $this->disableView();
            echo JsonComponent::encode($this->view->jsonContent);

            return;
        }

        $modulesConfig = Zend_Registry::get('configsModules');
        $modules = array('all', 'core');
        foreach ($modulesConfig as $key => $module) {
            $modules[] = $key;
        }
        $this->view->modulesLog = $modules;
    }

    /** Used to delete a list of log entries */
    public function deletelogAction()
    {
        $this->requireAdminPrivileges();
        $this->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $ids = $this->getParam('idList');
        $count = 0;
        foreach (explode(',', $ids) as $id) {
            if (!empty($id) && is_numeric($id)) {
                $count++;
                $dao = $this->Errorlog->load($id);
                if ($dao) {
                    $this->Errorlog->delete($dao);
                }
            }
        }

        echo JsonComponent::encode(array('message' => 'Successfully deleted '.$count.' entries.'));

        return;
    }

    /** admin dashboard view */
    public function dashboardAction()
    {
        $this->requireAdminPrivileges();
        $this->disableLayout();

        $this->view->dashboard = Zend_Registry::get('notifier')->callback('CALLBACK_CORE_GET_DASHBOARD');

        ksort($this->view->dashboard);
    }

    /** Ajax action for performing tree integrity checks */
    public function integritycheckAction()
    {
        $this->requireAdminPrivileges();
        $this->disableLayout();
        $this->disableView();

        $nOrphanedFolders = $this->Folder->countOrphans();
        $nOrphanedItems = $this->Item->countOrphans();
        $nOrphanedRevisions = $this->ItemRevision->countOrphans();
        $nOrphanedBitstreams = $this->Bitstream->countOrphans();
        // TODO: number of orphaned thumbnail records?

        $moduleIntegrityChecks = Zend_Registry::get('notifier')->callback(
            'CALLBACK_CORE_GET_DATABASE_INTEGRITY_CHECKS'
        );

        echo JsonComponent::encode(
            array(
                'nOrphanedFolders' => $nOrphanedFolders,
                'nOrphanedItems' => $nOrphanedItems,
                'nOrphanedRevisions' => $nOrphanedRevisions,
                'nOrphanedBitstreams' => $nOrphanedBitstreams,
                'moduleIntegrityChecks' => $moduleIntegrityChecks,
            )
        );
    }

    /**
     * This will delete all orphaned items and folders from the instance.
     * Call directly using /admin/removeorphans, there is no UI hook for this action.
     */
    public function removeorphansAction()
    {
        $this->requireAdminPrivileges();

        $model = $this->getParam('model');

        if (!isset($model) || !in_array($model, array('Bitstream', 'Item', 'ItemRevision', 'Folder'))
        ) {
            throw new Zend_Exception('Invalid model parameter for remove orphans');
        }
        $this->disableLayout();
        $this->disableView();

        $this->$model->removeOrphans($this->progressDao);
        echo JsonComponent::encode(array('status' => 'ok', 'message' => $model.' resources cleaned'));
    }

    /** upgrade database */
    public function upgradeAction()
    {
        $this->requireAdminPrivileges();
        $this->disableLayout();

        $db = Zend_Registry::get('dbAdapter');
        $dbtype = Zend_Registry::get('configDatabase')->database->adapter;
        $modulesConfig = Zend_Registry::get('configsModules');

        if ($this->_request->isPost()) {
            $this->disableView();
            $upgraded = false;
            foreach ($modulesConfig as $key => $module) {
                $this->Component->Upgrade->initUpgrade($key, $db, $dbtype);
                if ($this->Component->Upgrade->upgrade($module->version)) {
                    $upgraded = true;
                }
            }
            $this->Component->Upgrade->initUpgrade('core', $db, $dbtype);
            if ($this->Component->Upgrade->upgrade(Zend_Registry::get('configDatabase')->version)
            ) {
                $upgraded = true;
            }

            if ($upgraded) {
                echo JsonComponent::encode(array(true, 'Upgraded'));
            } else {
                echo JsonComponent::encode(array(false, 'Nothing to upgrade'));
            }

            return;
        }

        $modules = array();
        foreach ($modulesConfig as $key => $module) {
            $this->Component->Upgrade->initUpgrade($key, $db, $dbtype);
            $modules[$key]['target'] = $this->Component->Upgrade->getNewestVersion();
            $modules[$key]['targetText'] = $this->Component->Upgrade->getNewestVersion(true);
            $modules[$key]['currentText'] = $module->version;
            $modules[$key]['current'] = $this->Component->Upgrade->transformVersionToNumeric($module->version);
        }

        $this->view->modules = $modules;

        $this->Component->Upgrade->initUpgrade('core', $db, $dbtype);
        $core['target'] = $this->Component->Upgrade->getNewestVersion();
        $core['targetText'] = $this->Component->Upgrade->getNewestVersion(true);
        $core['currentText'] = Zend_Registry::get('configDatabase')->version;
        $core['current'] = $this->Component->Upgrade->transformVersionToNumeric(
            Zend_Registry::get('configDatabase')->version
        );
        $this->view->core = $core;
    }

    /**
     * called by the server-side file chooser
     */
    public function serversidefilechooserAction()
    {
        $this->requireAdminPrivileges();

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        // Display the tree
        $_POST['dir'] = urldecode($_POST['dir']);
        $files = array();
        if (strpos(strtolower(PHP_OS), 'win') === 0) {
            $files = array();
            for ($c = 'A'; $c <= 'Z'; $c++) {
                if (is_dir($c.':')) {
                    $files[] = $c.':';
                }
            }
        } else {
            $files[] = '/';
        }

        if (file_exists($_POST['dir']) || file_exists($files[0])) {
            if (file_exists($_POST['dir'])) {
                $files = scandir($_POST['dir']);
            }
            natcasesort($files);
            echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
            foreach ($files as $file) {
                if (file_exists($_POST['dir'].$file) && $file != '.' && $file != '..' && is_readable(
                        $_POST['dir'].$file
                    )
                ) {
                    if (is_dir($_POST['dir'].$file)) {
                        echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"".htmlentities(
                                $_POST['dir'].$file
                            )."/\">".htmlentities($file)."</a></li>";
                    } else {
                        // not a directory: a file!
                        $ext = preg_replace('/^.*\./', '', $file);
                        echo "<li class=\"file ext_".$ext."\"><a href=\"#\" rel=\"".htmlentities(
                                $_POST['dir'].$file
                            )."\">".htmlentities($file)."</a></li>";
                    }
                }
            }
            echo "</ul>";
        } else {
            echo "File ".$_POST['dir']." doesn't exist";
        }
    }

    /**
     */
    public function migratemidas2Action()
    {
        $this->requireAdminPrivileges();

        $this->assetstores = $this->Assetstore->getAll();
        $this->view->migrateForm = $this->Form->Migrate->createMigrateForm($this->assetstores);
        $this->view->assetstoreForm = $this->Form->Assetstore->createAssetstoreForm('../assetstore/add');

        if ($this->getRequest()->isPost()) {
            $this->disableLayout();
            $this->disableView();

            if (!$this->view->migrateForm->isValid($_POST)) {
                echo json_encode(array('error' => $this->t('The form is invalid. Missing values.')));

                return false;
            }

            $midas2_hostname = $_POST['midas2_hostname'];
            $midas2_port = $_POST['midas2_port'];
            $midas2_user = $_POST['midas2_user'];
            $midas2_password = $_POST['midas2_password'];
            $midas2_database = $_POST['midas2_database'];
            $midas2_assetstore = $_POST['midas2_assetstore'];
            $midas3_assetstore = $_POST['assetstore'];

            // Check that the assetstore is accessible
            if (!file_exists($midas2_assetstore)) {
                echo json_encode(array('error' => $this->t('MIDAS2 assetstore is not accessible.')));

                return false;
            }

            // Remove the last slash if any
            if ($midas2_assetstore[strlen($midas2_assetstore) - 1] == '\\' || $midas2_assetstore[strlen(
                    $midas2_assetstore
                ) - 1] == '/'
            ) {
                $midas2_assetstore = substr($midas2_assetstore, 0, strlen($midas2_assetstore) - 1);
            }

            $this->Component->MIDAS2Migration->midas2User = $midas2_user;
            $this->Component->MIDAS2Migration->midas2Password = $midas2_password;
            $this->Component->MIDAS2Migration->midas2Host = $midas2_hostname;
            $this->Component->MIDAS2Migration->midas2Database = $midas2_database;
            $this->Component->MIDAS2Migration->midas2Port = $midas2_port;
            $this->Component->MIDAS2Migration->midas2Assetstore = $midas2_assetstore;
            $this->Component->MIDAS2Migration->assetstoreId = $midas3_assetstore;

            try {
                $this->Component->MIDAS2Migration->migrate($this->userSession->Dao->getUserId());
            } catch (Zend_Exception $e) {
                echo json_encode(array('error' => $this->t($e->getMessage())));

                return false;
            }

            echo json_encode(array('message' => $this->t('Migration successful.')));
        }

        return true;
    }
}
