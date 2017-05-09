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

require_once BASE_PATH.'/core/controllers/components/UtilityComponent.php';

/** Generic controller base class. */
class MIDAS_GlobalController extends Zend_Controller_Action
{
    /** @var array */
    protected $Models = array();

    /**
     * Constructor.
     *
     * @param Zend_Controller_Request_Abstract $request request
     * @param Zend_Controller_Response_Abstract $response response
     * @param array $invokeArgs parameters
     * @throws Zend_Exception
     */
    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs = array()
    ) {
        if ($this->isDebug()) {
            $this->_controllerTimer = microtime(true);
        }
        $this->loadElements();
        parent::__construct($request, $response, $invokeArgs);
    }

    /** Pre-dispatch routines */
    public function preDispatch()
    {
        UtilityComponent::setTimeLimit(0);
        $enabledModules = Zend_Registry::get('modulesEnable');

        if ((int) Zend_Registry::get('configGlobal')->get('internationalization', 0) === 1) {
            /** @var SettingModel $settingModel */
            $settingModel = MidasLoader::loadModel('Setting');
            if ($settingModel->getValueByNameWithDefault('language', 'en') === 'fr') {
                $translate = new Zend_Translate('csv', BASE_PATH.'/core/translation/fr-main.csv', 'en');
                Zend_Registry::set('translator', $translate);
                $translators = array();

                foreach ($enabledModules as $enabledModule) {
                    if (file_exists(BASE_PATH.'/modules/'.$enabledModule.'/translation/fr-main.csv')) {
                        $translators[$enabledModule] = new Zend_Translate(
                            'csv',
                            BASE_PATH.'/modules/'.$enabledModule.'/translation/fr-main.csv',
                            'en'
                        );
                    } elseif (file_exists(BASE_PATH.'/privateModules/'.$enabledModule.'/translation/fr-main.csv')) {
                        $translators[$enabledModule] = new Zend_Translate(
                            'csv',
                            BASE_PATH.'/privateModules/'.$enabledModule.'/translation/fr-main.csv',
                            'en'
                        );
                    }

                    Zend_Registry::set('translatorsModules', $translators);
                }
            }
        }

        $configs = array();
        foreach ($enabledModules as $enabledModule) {
            if (file_exists(LOCAL_CONFIGS_PATH.'/'.$enabledModule.'.local.ini')) {
                $configs[$enabledModule] = new Zend_Config_Ini(LOCAL_CONFIGS_PATH.'/'.$enabledModule.'.local.ini', 'global');
            } elseif (file_exists(BASE_PATH.'/privateModules/'.$enabledModule.'/configs/module.ini')) {
                $configs[$enabledModule] = new Zend_Config_Ini(
                    BASE_PATH.'/privateModules/'.$enabledModule.'/configs/module.ini', 'global'
                );
            } else {
                $configs[$enabledModule] = new Zend_Config_Ini(
                    BASE_PATH.'/modules/'.$enabledModule.'/configs/module.ini',
                    'global'
                );
            }
        }

        Zend_Registry::set('configsModules', $configs);

        $forward = $this->getParam('forwardModule');
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (!isset($forward) && $request->getModuleName() == 'default') {
            foreach ($configs as $key => $config) {
                if (file_exists(
                    BASE_PATH.'/modules/'.$key.'/controllers/'.ucfirst(
                        $request->getControllerName()
                    ).'CoreController.php'
                )) {
                    include_once BASE_PATH.'/modules/'.$key.'/controllers/'.ucfirst(
                            $request->getControllerName()
                        ).'CoreController.php';
                    $name = ucfirst($key).'_'.ucfirst($request->getControllerName()).'CoreController';
                    $controller = new $name($request, $response);
                    if (method_exists($controller, $request->getActionName().'Action')) {
                        $this->forward(
                            $request->getActionName(),
                            $request->getControllerName().'Core',
                            $key,
                            array('forwardModule' => true)
                        );
                    }
                } elseif (file_exists(
                    BASE_PATH.'/privateModules/'.$key.'/controllers/'.ucfirst(
                        $request->getControllerName()
                    ).'CoreController.php'
                )) {
                    include_once BASE_PATH.'/privateModules/'.$key.'/controllers/'.ucfirst(
                            $request->getControllerName()
                        ).'CoreController.php';
                    $name = ucfirst($key).'_'.ucfirst($request->getControllerName()).'CoreController';
                    $controller = new $name($request, $response);
                    if (method_exists($controller, $request->getActionName().'Action')) {
                        $this->forward(
                            $request->getActionName(),
                            $request->getControllerName().'Core',
                            $key,
                            array('forwardModule' => true)
                        );
                    }
                }
            }
        }
        parent::preDispatch();
    }

    /**
     * Post-dispatch routines.
     *
     * Common usages for postDispatch() include rendering content in a site wide
     * template, link url correction, setting headers, etc.
     */
    public function postDispatch()
    {
        parent::postDispatch();
        $this->view->addHelperPath(BASE_PATH.'/core/views/helpers', 'Zend_View_Helper_');
    }

    /**
     * Fetch the logger from the Zend registry.
     *
     * @return Zend_Log
     */
    public function getLogger()
    {
        return Zend_Registry::get('logger');
    }

    /** Load components, forms, and models. */
    public function loadElements()
    {
        Zend_Registry::set('models', array());
        if (isset($this->_models)) {
            MidasLoader::loadModels($this->_models);
        }
        $modelsArray = Zend_Registry::get('models');
        foreach ($modelsArray as $key => $tmp) {
            $this->$key = $tmp;
        }

        if (isset($this->_daos)) {
            foreach ($this->_daos as $dao) {
                Zend_Loader::loadClass($dao.'Dao', BASE_PATH.'/core/models/dao');
            }
        }

        Zend_Registry::set('components', array());

        if (isset($this->_components)) {
            foreach ($this->_components as $component) {
                $nameComponent = $component.'Component';
                Zend_Loader::loadClass($nameComponent, BASE_PATH.'/core/controllers/components');
                if (!isset($this->Component)) {
                    $this->Component = new stdClass();
                }
                if (!class_exists($nameComponent)) {
                    throw new Zend_Exception('Unable to find '.$nameComponent);
                }
                $this->Component->$component = new $nameComponent();
            }
        }

        Zend_Registry::set('forms', array());
        if (isset($this->_forms)) {
            foreach ($this->_forms as $forms) {
                $nameForm = $forms.'Form';

                Zend_Loader::loadClass($nameForm, BASE_PATH.'/core/controllers/forms');
                if (!isset($this->Form)) {
                    $this->Form = new stdClass();
                }
                if (!class_exists($nameForm)) {
                    throw new Zend_Exception('Unable to find '.$nameForm);
                }
                $this->Form->$forms = new $nameForm();
            }
        }
    }

    /**
     * Is Debug mode on.
     *
     * @return bool
     */
    public function isDebug()
    {
        return Zend_Registry::get('configGlobal')->get('environment', 'production') !== 'production';
    }

    /**
     * Get environnement set in the config.
     *
     * @return string
     */
    public function getEnvironment()
    {
        return Zend_Registry::get('configGlobal')->get('environment', 'production');
    }

    /**
     * Return a data directory.
     *
     * @param string $subDirectory
     * @param bool $createDirectory
     * @return string
     */
    protected function getDataDirectory($subDirectory = '', $createDirectory = true)
    {
        return UtilityComponent::getDataDirectory($subDirectory, $createDirectory);
    }

    /**
     * Return a temporary directory.
     *
     * @param string $subDirectory
     * @param bool $createDirectory
     * @return string
     */
    protected function getTempDirectory($subDirectory = 'misc', $createDirectory = true)
    {
        return UtilityComponent::getTempDirectory($subDirectory, $createDirectory);
    }

    /**
     * Return an array of form elements.
     *
     * @param Zend_Form $form
     * @return array
     */
    public function getFormAsArray(Zend_Form $form)
    {
        $array = array();
        $array['action'] = $form->getAction();
        $array['method'] = $form->getMethod();
        foreach ($form->getElements() as $element) {
            $element->removeDecorator('HtmlTag');
            $element->removeDecorator('Label');
            $element->removeDecorator('DtDdWrapper');
            $array[$element->getName()] = $element;
        }

        return $array;
    }

  /**
   * Return a sanitized request parameter useful to prevent XSS attack.
   */
  public function getSafeParam($paramName, $trim = false)
  {
      $value = $this->getParam($paramName);
      if ($trim) {
          $value = trim($value);
      }
      return $this->sanitize($value);
  }

  protected function sanitize($value)
  {
      return htmlspecialchars($value, ENT_QUOTES);
  }
}
