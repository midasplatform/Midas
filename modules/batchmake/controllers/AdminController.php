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

/** Admin controller for the batchmake module. */
class Batchmake_AdminController extends Batchmake_AppController
{
    /** @var array */
    public $_moduleComponents = array('KWBatchmake');

    /** Index action */
    public function indexAction()
    {
        $this->requireAdminPrivileges();

        $this->view->pageTitle = 'BatchMake Module Configuration';
        $config = $this->ModuleComponent->KWBatchmake->loadConfigProperties(null, false);
        $form = new Batchmake_Form_Admin();

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();

            if ($form->isValid($data)) {
                $values = $form->getValues();

                foreach ($values as $key => $value) {
                    if ($key !== MIDAS_BATCHMAKE_CSRF_TOKEN && !is_null($value)) {
                        $config[MIDAS_BATCHMAKE_GLOBAL_CONFIG_NAME][$this->moduleName.'.'.$key] = $value;
                    }
                }

                UtilityComponent::createInitFile(MIDAS_BATCHMAKE_MODULE_LOCAL_CONFIG, $config);
            }

            $form->populate($data);
        } else {
            $elements = $form->getElements();

            foreach ($elements as $element) {
                $batchMakeConfig = $this->ModuleComponent->KWBatchmake->filterBatchmakeConfigProperties($config);
                $defaultConfig = $this->createDefaultConfig($batchMakeConfig);
                $name = $element->getName();

                if ($name !== MIDAS_BATCHMAKE_CSRF_TOKEN && $name !== MIDAS_BATCHMAKE_SUBMIT_CONFIG) {
                    $value = $defaultConfig[$name];

                    if (!is_null($value)) {
                        $form->setDefault($name, $value);
                    }
                }
            }
        }

        $this->view->form = $form;
        session_start();
    }

    /**
     * will create default paths in the temporary directory
     * for any properties not already set, except for the
     * condor bin dir; imposing a firmer hand on the user
     *
     * @param array $currentConfig current configuration
     * @return array
     */
    protected function createDefaultConfig($currentConfig)
    {
        $defaultConfigDirs = array(
            MIDAS_BATCHMAKE_TMP_DIR_PROPERTY => MIDAS_BATCHMAKE_DEFAULT_TMP_DIR,
            MIDAS_BATCHMAKE_BIN_DIR_PROPERTY => MIDAS_BATCHMAKE_DEFAULT_BIN_DIR,
            MIDAS_BATCHMAKE_SCRIPT_DIR_PROPERTY => MIDAS_BATCHMAKE_DEFAULT_SCRIPT_DIR,
            MIDAS_BATCHMAKE_APP_DIR_PROPERTY => MIDAS_BATCHMAKE_DEFAULT_APP_DIR,
            MIDAS_BATCHMAKE_DATA_DIR_PROPERTY => MIDAS_BATCHMAKE_DEFAULT_DATA_DIR,
        );

        $returnedConfig = array();

        foreach ($currentConfig as $configProp => $configDir) {
            if ((!isset($configProp) || !isset($configDir) || empty($configDir)) && array_key_exists(
                    $configProp,
                    $defaultConfigDirs
                )
            ) {
                $returnedConfig[$configProp] = UtilityComponent::getTempDirectory($defaultConfigDirs[$configProp]);
            } else {
                $returnedConfig[$configProp] = $configDir;
            }
        }

        return $returnedConfig;
    }
}
