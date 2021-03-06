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

/** Error management */
class NotifyErrorComponent extends AppComponent
{
    /** @var string */
    protected $_environment;

    /** @var Zend_Session_Namespace */
    protected $_session;

    /** @var ArrayObject */
    protected $_error;

    /**
     * Initialize notifier.
     *
     * @param string $environment
     * @param ArrayObject $error
     * @param Zend_Session_Namespace $session
     * @param array $server
     */
    public function initNotifier(
        $environment,
        ArrayObject $error,
        Zend_Session_Namespace $session,
        array $server
    ) {
        $this->_environment = $environment;
        $this->_error = $error;
        $this->_session = $session;
        $this->_server = $server;
    }

    /**
     * Handle fatal errors.
     *
     * @param Zend_Log $logger
     */
    public function fatalError($logger)
    {
        if (!is_null(error_get_last())) {
            $e = error_get_last();
            $environment = Zend_Registry::get('configGlobal')->get('environment', 'production');
            switch ($environment) {
                case 'production':
                    $message = 'The system has encountered the following error:<br/><h3>';
                    $message .= htmlspecialchars($e['message'], ENT_QUOTES, 'UTF-8').'<br/>';
                    $message .= 'In '.htmlspecialchars($e['file'], ENT_QUOTES, 'UTF-8').', line: '.htmlspecialchars($e['line'], ENT_QUOTES, 'UTF-8').'<br/>';
                    $message .= 'At '.date('H:i:s Y-m-d').'</h3><br/>';
                    $message .= 'Please notify your administrator with this information.<br/>';
                    if ($e['type'] == E_NOTICE) {
                        $e['typeText'] = 'E_NOTICE';
                    } elseif ($e['type'] == E_ERROR) {
                        $e['typeText'] = 'E_ERROR';
                    } elseif ($e['type'] == 4) {
                        $e['typeText'] = '4';
                    } elseif ($e['type'] == E_WARNING) {
                        $e['typeText'] = 'E_WARNING';
                    } elseif ($e['type'] == E_PARSE) {
                        $e['typeText '] = 'E_PARSE';
                    } elseif ($e['type'] == E_RECOVERABLE_ERROR) {
                        $e['typeText '] = 'E_RECOVERABLE_ERROR';
                    } elseif ($e['type'] == E_COMPILE_ERROR) {
                        $e['typeText '] = 'E_COMPILE_ERROR';
                    } else {
                        return;
                    }
                    header('content-type: text/html');
                    if (count(ob_list_handlers()) > 0) {
                        ob_clean();
                    }
                    echo $message;
                    $this->_environment = $environment;
                    break;
                default:
                    $this->_server = $_SERVER;
                    if ($e['type'] == E_NOTICE) {
                        $e['typeText'] = 'E_NOTICE';
                    } elseif ($e['type'] == E_ERROR) {
                        $e['typeText'] = 'E_ERROR';
                    } elseif ($e['type'] == 4) {
                        $e['typeText'] = '4';
                    } elseif ($e['type'] == E_WARNING) {
                        $e['typeText'] = 'E_WARNING';
                    } elseif ($e['type'] == E_PARSE) {
                        $e['typeText '] = 'E_PARSE';
                    } elseif ($e['type'] == E_RECOVERABLE_ERROR) {
                        $e['typeText '] = 'E_RECOVERABLE_ERROR';
                    } elseif ($e['type'] == E_COMPILE_ERROR) {
                        $e['typeText '] = 'E_COMPILE_ERROR';
                    } else {
                        return;
                    }

                    if (count(ob_list_handlers()) > 0) {
                        ob_clean();
                    }

                    $db = Zend_Registry::get('dbAdapter');
                    $table = $db->listTables();
                    if (file_exists(LOCAL_CONFIGS_PATH.'/database.local.ini') && empty($table)
                    ) {
                        $fc = Zend_Controller_Front::getInstance();
                        $webroot = $fc->getBaseUrl();
                        echo "Midas Server is not installed. <a href='".$webroot."/install?reset=true'>Click here to reset Midas Server and go to the installation page.</a>";

                        return;
                    }

                    header('content-type: text/plain');
                    echo $this->getFatalErrorMessage($e);
            }
            $logger->crit($this->getFatalErrorMessage($e));
            $logger->__destruct();
        }
    }

    /**
     * Handle warning.
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @throws Zend_Exception
     */
    public function warningError($errno, $errstr, $errfile, $errline)
    {
        if ($errno == E_WARNING && Zend_Registry::get('configGlobal')->get('environment', 'production') !== 'production'
        ) {
            $message = 'Warning: '.htmlspecialchars($errstr, ENT_QUOTES, 'UTF-8')."<br/>\n on line ".htmlspecialchars($errline, ENT_QUOTES, 'UTF-8').' in file '.htmlspecialchars($errfile, ENT_QUOTES, 'UTF-8')."<br/>\n";
            $this->getLogger()->warn($message);
            echo $message;
        }

        if ($errno == E_NOTICE && Zend_Registry::get('configGlobal')->get('environment', 'production') !== 'production'
        ) {
            $message = 'Notice : '.htmlspecialchars($errstr, ENT_QUOTES, 'UTF-8')."<br/>\non line ".htmlspecialchars($errline, ENT_QUOTES, 'UTF-8').' in file '.htmlspecialchars($errfile, ENT_QUOTES, 'UTF-8')."<br/>\n";
            $this->getLogger()->warn($message);
            echo $message;
        }
    }

    /**
     * Page url.
     *
     * @return string
     */
    public function curPageURL()
    {
        if (Zend_Registry::get('configGlobal')->get('environment', 'production') === 'testing') {
            return 'http://localhost';
        }
        $pageURL = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $pageURL .= 's';
        }
        $pageURL .= '://';
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') {
            $pageURL .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
        } else {
            $pageURL .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        }

        return $pageURL;
    }

    /**
     * create fatal error message.
     *
     * @param array $e
     * @return string
     */
    public function getFatalErrorMessage($e)
    {
        $message = 'Fatal Error: ';
        $message .= htmlspecialchars(print_r($e, true), ENT_QUOTES, 'UTF-8');
        $message .= "\n\n";
        $message .= 'URL: '.htmlspecialchars($this->curPageURL(), ENT_QUOTES, 'UTF-8');
        $message .= "\n\n";
        if (!empty($this->_server['SERVER_ADDR'])) {
            $message .= 'Server IP: '.htmlspecialchars($this->_server['SERVER_ADDR'], ENT_QUOTES, 'UTF-8')."\n";
        }

        if (!empty($this->_server['HTTP_USER_AGENT'])) {
            $message .= 'User agent: '.htmlspecialchars($this->_server['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8')."\n";
        }

        if (!empty($this->_server['HTTP_X_REQUESTED_WITH'])) {
            $message .= 'Request type: '.htmlspecialchars($this->_server['HTTP_X_REQUESTED_WITH'], ENT_QUOTES, 'UTF-8')."\n";
        }

        $message .= 'Server time: '.date('Y-m-d H:i:s')."\n";

        if (!empty($this->_server['HTTP_REFERER'])) {
            $message .= 'Referer: '.htmlspecialchars($this->_server['HTTP_REFERER'], ENT_QUOTES, 'UTF-8')."\n";
        }
        $message .= "Parameters (post): Array\n(\n";
        foreach ($_POST as $key => $value) {
            if (strpos(strtolower($key), 'password') !== false) {
                $message .= '    ['.htmlspecialchars($key, ENT_QUOTES, 'UTF-8')."] => --redacted--\n";
            } else {
                $message .= '    ['.htmlspecialchars($key, ENT_QUOTES, 'UTF-8').'] => '.htmlspecialchars($value, ENT_QUOTES, 'UTF-8')."\n";
            }
        }
        $message .= ")\nParameters (get): ".htmlspecialchars(print_r($_GET, true), ENT_QUOTES, 'UTF-8')."\n\n";

        return $message;
    }

    /**
     * Create exception message error.
     *
     * @return string
     */
    public function getFullErrorMessage()
    {
        $message = '';

        if (!empty($this->_server['SERVER_ADDR'])) {
            $message .= 'Server IP: '.htmlspecialchars($this->_server['SERVER_ADDR'], ENT_QUOTES, 'UTF-8')."\n";
        }
        if (!empty($this->_server['REMOTE_ADDR'])) {
            $message .= 'Client IP: '.htmlspecialchars($this->_server['REMOTE_ADDR'], ENT_QUOTES, 'UTF-8')."\n";
        }

        if (!empty($this->_server['HTTP_USER_AGENT'])) {
            $message .= 'User agent: '.htmlspecialchars($this->_server['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8')."\n";
        }

        if (!empty($this->_server['HTTP_X_REQUESTED_WITH'])) {
            $message .= 'Request type: '.htmlspecialchars($this->_server['HTTP_X_REQUESTED_WITH'], ENT_QUOTES, 'UTF-8')."\n";
        }

        $message .= 'Server time: '.date('Y-m-d H:i:s')."\n";
        $message .= 'RequestURI: '.htmlspecialchars($this->_error->request->getRequestUri(), ENT_QUOTES, 'UTF-8')."\n";

        if (!empty($this->_server['HTTP_REFERER'])) {
            $message .= 'Referer: '.htmlspecialchars($this->_server['HTTP_REFERER'], ENT_QUOTES, 'UTF-8')."\n";
        }

        $message .= '<b>Message: '.htmlspecialchars($this->_error->exception->getMessage(), ENT_QUOTES, 'UTF-8')."</b>\n\n";
        $message .= "Trace:\n".htmlspecialchars($this->_error->exception->getTraceAsString(), ENT_QUOTES, 'UTF-8')."\n\n";
        $message .= 'Request data: '.htmlspecialchars(var_export($this->_error->request->getParams(), true), ENT_QUOTES, 'UTF-8')."\n\n";

        $it = $this->_session->getIterator();

        $message .= "Session data:\n";
        foreach ($it as $key => $value) {
            $message .= htmlspecialchars($key, ENT_QUOTES, 'UTF-8').': '.htmlspecialchars(var_export($value, true), ENT_QUOTES, 'UTF-8')."\n";
        }
        $message .= "\n";

        return $message;
    }

    /**
     * Create short error message.
     *
     * @return string
     */
    public function getShortErrorMessage()
    {
        $message = '';
        switch ($this->_environment) {
            case 'production':
                $message = 'The system has encountered the following error:<br/><h3>';
                $message .= htmlspecialchars($this->_error->exception->getMessage(), ENT_QUOTES, 'UTF-8').'<br/>';
                $message .= 'In '.htmlspecialchars($this->_error->exception->getFile(), ENT_QUOTES, 'UTF-8').', line: '.htmlspecialchars($this->_error->exception->getLine(
                    ), ENT_QUOTES, 'UTF-8').'<br/>';
                $message .= 'At '.date('H:i:s Y-m-d').'</h3><br/>';
                $message .= 'Please notify your administrator with this information.<br/>';
                break;
            default:
                $message .= 'Message: '.htmlspecialchars($this->_error->exception->getMessage(), ENT_QUOTES, 'UTF-8')."\n\n";
                $message .= "Trace:\n".htmlspecialchars($this->_error->exception->getTraceAsString(), ENT_QUOTES, 'UTF-8')."\n\n";
        }

        return $message;
    }
}
