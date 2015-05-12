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

/** Error Controller */
class ErrorController extends AppController
{
    public $_models = array();
    public $_daos = array();
    public $_components = array('NotifyError', 'Utility');
    public $_forms = array();
    private $_error;
    private $_environment;

    /** Init Controller */
    public function init()
    {
        parent::init();

        $error = $this->getParam('error_handler');
        if (!isset($error) || empty($error)) {
            return;
        }
        $session = new Zend_Session_Namespace('Auth_User');
        $db = Zend_Registry::get('dbAdapter');

        $environment = Zend_Registry::get('configGlobal')->environment;
        $this->_environment = $environment;
        $this->Component->NotifyError->initNotifier($environment, $error, $session, $_SERVER);

        $this->_error = $error;

        $this->_environment = $environment;
        $this->view->setScriptPath(BASE_PATH.'/core/views');
    }

    /** Error Action */
    public function errorAction()
    {
        $error = $this->getParam('error_handler');
        if (!isset($error) || empty($error)) {
            $this->view->message = 'Page not found';

            return;
        }

        $controller = $error->request->getParams();
        $controller = $controller['controller'];
        if ($controller != 'install' && !file_exists(LOCAL_CONFIGS_PATH.'/database.local.ini')
        ) {
            $this->view->message = "Midas is not installed. Please go the <a href = '".$this->view->webroot."/install'> install page</a>.";

            return;
        }
        switch ($this->_error->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = 'Page not found';
                break;
            default:
                $code = $this->_error->exception->getCode();
                $this->view->code = $code;
                $this->view->exceptionText = $this->_error->exception->getMessage();
                if ($code == 0) {
                    $this->getResponse()->setHttpResponseCode(500);
                } elseif ($code >= 400 && $code <= 417) {
                    $this->getResponse()->setHttpResponseCode($code);
                    if ($code == 403) {
                        if ($this->logged) {
                            $this->view->header = 'Access Denied';
                        } else {
                            $this->haveToBeLogged();

                            return;
                        }
                    } elseif ($code == 404) {
                        $this->view->header = 'Not Found';
                    }
                }
                $this->_applicationError();
                break;
        }
        $fullMessage = $this->Component->NotifyError->getFullErrorMessage();
        if (isset($this->fullMessage)) {
            $this->getLogger()->warn($this->fullMessage);
        } else {
            $this->getLogger()->warn('URL: '.$this->Component->NotifyError->curPageURL()."\n".$fullMessage);
        }
    }

    private function _applicationError()
    {
        $fullMessage = $this->Component->NotifyError->getFullErrorMessage();
        $shortMessage = $this->Component->NotifyError->getShortErrorMessage();
        $this->fullMessage = $fullMessage;

        switch ($this->_environment) {
            case 'production':
                $this->view->message = $shortMessage;
                break;
            case 'testing':
                $this->disableLayout();
                $this->disableView();

                $this->getResponse()->appendBody($shortMessage);
                break;
            default:
                $this->view->message = nl2br($fullMessage);
        }
    }
}
