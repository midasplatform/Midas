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

/** Admin Controller. */
class AdminController extends AppController
{
    public $_models = array('Assetstore', 'Bitstream', 'Item', 'ItemRevision', 'Folder', 'License', 'Module', 'Setting');
    public $_daos = array();
    public $_components = array('Upgrade', 'Utility', 'MIDAS2Migration');
    public $_forms = array('Admin', 'Assetstore', 'Migrate');

    /** init the controller */
    public function init()
    {
        if ($this->isDemoMode()) {
            $this->disableView();

            return false;
        }
    }

    /** run a task */
    public function taskAction()
    {
        $this->requireAdminPrivileges();

        $task = $this->getParam('task');
        $params = $this->getParam('params');
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
        $this->view->header = 'Administration';

        $configForm = $this->Form->Admin->createConfigForm();
        $formArray = $this->getFormAsArray($configForm);
        $formArray['title']->setValue($this->Setting->getValueByName('title'));
        $formArray['description']->setValue($this->Setting->getValueByName('description'));
        $formArray['language']->setValue($this->Setting->getValueByNameWithDefault('language', 'en'));
        $formArray['time_zone']->setValue($this->Setting->getValueByNameWithDefault('time_zone', 'UTC'));
        $formArray['dynamic_help']->setValue((int) $this->Setting->getValueByNameWithDefault('dynamic_help', 0));
        $formArray['allow_password_reset']->setValue((int) $this->Setting->getValueByNameWithDefault('allow_password_reset', 0));
        $formArray['close_registration']->setValue((int) $this->Setting->getValueByNameWithDefault('close_registration', 1));
        $formArray['gravatar']->setValue((int) $this->Setting->getValueByNameWithDefault('gravatar', 0));
        $this->view->configForm = $formArray;

        $this->view->selectedLicense = (int) $this->Setting->getValueByNameWithDefault('default_license', 1);
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
                $this->Setting->setConfig('title', $this->getParam('title'));
                $this->Setting->setConfig('description', $this->getParam('description'));
                $this->Setting->setConfig('language', $this->getParam('language'));
                $this->Setting->setConfig('time_zone', $this->getParam('time_zone'));
                $this->Setting->setConfig('dynamic_help', (int) $this->getParam('dynamic_help'));
                $this->Setting->setConfig('allow_password_reset', (int) $this->getParam('allow_password_reset'));
                $this->Setting->setConfig('close_registration', (int) $this->getParam('close_registration'));
                $this->Setting->setConfig('gravatar', (int) $this->getParam('gravatar'));
                $this->Setting->setConfig('default_license', (int) $this->getParam('licenseSelect'));
                echo JsonComponent::encode(array(true, 'Changes saved'));
            }
            if (isset($submitModule)) {
                $moduleName = $this->getParam('modulename');
                $moduleEnabled = $this->getParam('modulevalue');
                $moduleDao = $this->Module->getByName($moduleName);
                if ($moduleDao === false) {
                    $this->Component->Utility->installModule($moduleName);
                    $moduleDao = $this->Module->getByName($moduleName);
                }
                $moduleDao->setEnabled($moduleEnabled);
                $this->Module->save($moduleDao);
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
                $this->Setting->setConfig('default_assetstore', $assetstores[$key]->getKey());
                break;
            }
        }
        $this->view->assetstores = $assetstores;
        $this->view->assetstoreForm = $this->Form->Assetstore->createAssetstoreForm();

        // get modules
        //
        $adapter = Zend_Registry::get('configDatabase')->database->adapter;
        foreach ($allModules as $key => $module) {
            if (file_exists(BASE_PATH.'/modules/'.$key.'/controllers/ConfigController.php')) {
                $allModules[$key]->configPage = 'config';
            } elseif (file_exists(BASE_PATH.'/privateModules/'.$key.'/controllers/ConfigController.php')) {
                $allModules[$key]->configPage = 'config';
            } elseif (file_exists(BASE_PATH.'/modules/'.$key.'/controllers/AdminController.php')) {
                $allModules[$key]->configPage = 'admin';
            } elseif (file_exists(BASE_PATH.'/privateModules/'.$key.'/controllers/AdminController.php')) {
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
                $category = 'Misc';
            } else {
                $category = ucfirst($module->category);
            }
            if (!isset($modulesList[$category])) {
                $modulesList[$category] = array();
                $countModules[$category] = array('visible' => 0, 'hidden' => 0);
            }
            $modulesList[$category][$k] = $module;
            if ($module->dbOk && $module->dependenciesExist) {
                ++$countModules[$category]['visible'];
            } else {
                ++$countModules[$category]['hidden'];
            }
        }

        foreach ($modulesList as $k => $l) {
            ksort($modulesList[$k]);
        }

        ksort($modulesList);
        $this->view->countModules = $countModules;
        $this->view->modulesList = $modulesList;

        $enabledModules = Zend_Registry::get('modulesEnable');
        $this->view->modulesEnable = $enabledModules;

        $this->view->databaseType = $adapter;
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

        $version = UtilityComponent::getCurrentModuleVersion('core');
        if ($version === false) {
            throw new Zend_Exception('Core version is undefined.');
        }

        if ($this->_request->isPost()) {
            $this->disableView();
            $upgraded = false;
            foreach ($modulesConfig as $key => $module) {
                $this->Component->Upgrade->initUpgrade($key, $db, $dbtype);
                $currentModuleVersion = UtilityComponent::getCurrentModuleVersion($key);
                if ($this->Component->Upgrade->upgrade($currentModuleVersion)) {
                    $upgraded = true;
                }
            }
            $this->Component->Upgrade->initUpgrade('core', $db, $dbtype);
            if ($this->Component->Upgrade->upgrade($version)) {
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
            $modules[$key]['target'] = UtilityComponent::getLatestModuleVersion($key);
            $modules[$key]['current'] = UtilityComponent::getCurrentModuleVersion($key);
        }

        $this->view->modules = $modules;

        $this->Component->Upgrade->initUpgrade('core', $db, $dbtype);
        $core['target'] = UtilityComponent::getLatestModuleVersion('core');
        $core['current'] = UtilityComponent::getCurrentModuleVersion('core');
        $this->view->core = $core;
    }

    /**
     * called by the server-side file chooser.
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
            for ($c = 'A'; $c <= 'Z'; ++$c) {
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
            echo '<ul class="jqueryFileTree" style="display: none;">';
            foreach ($files as $file) {
                if (file_exists($_POST['dir'].$file) && $file != '.' && $file != '..' && is_readable(
                        $_POST['dir'].$file
                    )
                ) {
                    if (is_dir($_POST['dir'].$file)) {
                        echo '<li class="directory collapsed"><a href="#" rel="'.htmlspecialchars(
                                $_POST['dir'].$file, ENT_QUOTES, 'UTF-8'
                            ).'/">'.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').'</a></li>';
                    } else {
                        // not a directory: a file!
                        $ext = preg_replace('/^.*\./', '', $file);
                        echo '<li class="file ext_'.$ext.'"><a href="#" rel="'.htmlspecialchars(
                                $_POST['dir'].$file, ENT_QUOTES, 'UTF-8'
                            ).'">'.htmlspecialchars($file, ENT_QUOTES, 'UTF-8').'</a></li>';
                    }
                }
            }
            echo '</ul>';
        } else {
            echo 'File '.$_POST['dir']." doesn't exist";
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
