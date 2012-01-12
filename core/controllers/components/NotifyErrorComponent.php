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

/** Error management*/
class NotifyErrorComponent  extends AppComponent
  {
  protected $_environment;
  protected $_mailer;
  protected $_session;
  protected $_error;
  protected $_profiler;

  /** Constructor */
  public function __construct()
    {

    }

  /** Init*/
  public function initNotifier($environment, ArrayObject $error, Zend_Mail $mailer, Zend_Session_Namespace $session, Zend_Db_Profiler $profiler, Array $server)
    {
    $this->_environment = $environment;
    $this->_mailer = $mailer;
    $this->_error = $error;
    $this->_session = $session;
    $this->_profiler = $profiler;
    $this->_server = $server;
    }

  /** Handle fatal Errors*/
  public function fatalEror($logger, $mailer)
    {
    if(!is_null(error_get_last()))
      {
      $e = error_get_last();
      $environment = Zend_Registry::get('configGlobal')->environment;
      switch($environment)
        {
        case 'production':
          $message = "The system has encountered the following error:<br/><h3>";
          $message .= $e->message . "<br/>";
          $message .= "In " . $e->file . ", line: " . $e->line . "<br/>";
          $message .= "At " . date("H:i:s Y-m-d") . "</h3><br/>";
          $message .= "Please notify your administrator with this information.<br/>";
          if($e['type'] == E_NOTICE)
            {
            $e['typeText'] = 'E_NOTICE';
            }
          elseif($e['type'] == E_ERROR )
            {
            $e['typeText'] = 'E_ERROR';
            }
          elseif($e['type'] == 4 )
            {
            $e['typeText'] = '4';
            }
          elseif($e['type'] == E_WARNING )
            {
            $e['typeText'] = 'E_WARNING';
            }
          elseif($e['type'] == E_PARSE)
            {
            $e['typeText '] = 'E_PARSE';
            }
          elseif($e['type'] == E_RECOVERABLE_ERROR)
            {
            $e['typeText '] = 'E_RECOVERABLE_ERROR';
            }
          elseif($e['type'] == E_COMPILE_ERROR)
            {
            $e['typeText '] = 'E_COMPILE_ERROR';
            }
          else
            {
            return;
            }
          header('content-type: text/plain');
          if(count(ob_list_handlers()) > 0)
            {
            ob_clean();
            }
          echo $message;
          $this->_mailer = $mailer;
          $this->_environment = $environment;
          break;
        default:
          $this->_server = $_SERVER;
          if($e['type'] == E_NOTICE)
            {
            $e['typeText'] = 'E_NOTICE';
            }
          elseif($e['type'] == E_ERROR )
            {
            $e['typeText'] = 'E_ERROR';
            }
          elseif($e['type'] == 4 )
            {
            $e['typeText'] = '4';
            }
          elseif($e['type'] == E_WARNING )
            {
            $e['typeText'] = 'E_WARNING';
            }
          elseif($e['type'] == E_PARSE)
            {
            $e['typeText '] = 'E_PARSE';
            }
          elseif($e['type'] == E_RECOVERABLE_ERROR)
            {
            $e['typeText '] = 'E_RECOVERABLE_ERROR';
            }
          elseif($e['type'] == E_COMPILE_ERROR)
            {
            $e['typeText '] = 'E_COMPILE_ERROR';
            }
          else
            {
            return;
            }

          if(count(ob_list_handlers()) > 0)
            {
            ob_clean();
            }

          $db = Zend_Registry::get('dbAdapter');
          $table = $db->listTables();
          if(file_exists(BASE_PATH.'/core/configs/database.local.ini') && empty($table))
            {
            $fc = Zend_Controller_Front::getInstance();
            $webroot = $fc->getBaseUrl();
            echo "MIDAS is not installed. <a href='".$webroot."/install?reset=true'>Click here to reset MIDAS and go to the installation page.</a>";
            return;
            }

          header('content-type: text/plain');
          echo $this->getFatalErrorMessage($e);
        }
      $logger->crit($this->getFatalErrorMessage($e));
      $logger->__destruct();
      }
    } // end fatalEror

  /** handle warning*/
  public function warningError($errno, $errstr, $errfile, $errline)
    {
    if($errno == E_WARNING && Zend_Registry::get('configGlobal')->environment != 'production')
      {
      $message = "Warning: ".$errstr."<br/>\n on line ".$errline." in file ".$errfile."<br/>\n";
      $this->getLogger()->warn($message);
      echo $message;
      }

    if($errno == E_NOTICE && Zend_Registry::get('configGlobal')->environment != 'production')
      {
      $message = "Notice : ".$errstr."<br/>\non line ".$errline." in file ".$errfile."<br/>\n";
      $this->getLogger()->warn($message);
      echo $message;
      }
    }//end warningError

  /** Page url*/
  public function curPageURL()
    {
    if(Zend_Registry::get('configGlobal')->environment == 'testing')
      {
      return 'http://localhost';
      }
    $pageURL = 'http';
    if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
      {
      $pageURL .= "s";
      }
    $pageURL .= "://";
    if($_SERVER["SERVER_PORT"] != "80")
      {
      $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
      }
    else
      {
      $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
      }
    return $pageURL;
    }//end curPageURL

  /** create Fatal Error Message*/
  public function getFatalErrorMessage($e)
    {
    $message = "Fatal Error: ";
    $message .=  print_r($e, true);
    $message .=  "\n\n";
    $message .=  "URL: ".$this->curPageURL();
    $message .=  "\n\n";
    if(!empty($this->_server['SERVER_ADDR']))
      {
      $message .= "Server IP: " . $this->_server['SERVER_ADDR'] . "\n";
      }

    if(!empty($this->_server['HTTP_USER_AGENT']))
      {
      $message .= "User agent: " . $this->_server['HTTP_USER_AGENT'] . "\n";
      }

    if(!empty($this->_server['HTTP_X_REQUESTED_WITH']))
      {
      $message .= "Request type: " . $this->_server['HTTP_X_REQUESTED_WITH'] . "\n";
      }

    $message .= "Server time: " . date("Y-m-d H:i:s") . "\n";

    if(!empty($this->_server['HTTP_REFERER']))
      {
      $message .= "Referer: " . $this->_server['HTTP_REFERER'] . "\n";
      }
    $message .= "Parameters (post): ".print_r($_POST, true)."\n";
    $message .= "Parameters (get): ".print_r($_GET, true)."\n\n";
    return $message;
    }

  /** Create Exception message error*/
  public function getFullErrorMessage()
    {
    $message = '';

    if(!empty($this->_server['SERVER_ADDR']))
      {
      $message .= "Server IP: " . $this->_server['SERVER_ADDR'] . "\n";
      }

    if(!empty($this->_server['HTTP_USER_AGENT']))
      {
      $message .= "User agent: " . $this->_server['HTTP_USER_AGENT'] . "\n";
      }

    if(!empty($this->_server['HTTP_X_REQUESTED_WITH']))
      {
      $message .= "Request type: " . $this->_server['HTTP_X_REQUESTED_WITH'] . "\n";
      }

    $message .= "Server time: " . date("Y-m-d H:i:s") . "\n";
    $message .= "RequestURI: " . $this->_error->request->getRequestUri() . "\n";

    if(!empty($this->_server['HTTP_REFERER']))
      {
      $message .= "Referer: " . $this->_server['HTTP_REFERER'] . "\n";
      }

    $message .= "<b>Message: " . $this->_error->exception->getMessage() . "</b>\n\n";
    $message .= "Trace:\n" . $this->_error->exception->getTraceAsString() . "\n\n";
    $message .= "Request data: " . var_export($this->_error->request->getParams(), true) . "\n\n";

    $it = $this->_session->getIterator();

    $message .= "Session data:\n";
    foreach($it as $key => $value)
      {
      $message .= $key . ": " . var_export($value, true) . "\n";
      }
    $message .= "\n";

    if($this->_profiler->getLastQueryProfile() !== false)
      {
      $query = $this->_profiler->getLastQueryProfile()->getQuery();
      $queryParams = $this->_profiler->getLastQueryProfile()->getQueryParams();
      $message .= "Last database query: " . $query . "\n\n";
      }
    return $message;
    }//end getFullErrorMessage

  /** Create short error message */
  public function getShortErrorMessage()
    {
    $message = '';
    switch($this->_environment)
      {
      case 'production':
        $message = "The system has encountered the following error:<br/><h3>";
        $message .= $this->_error->exception->getMessage() . "<br/>";
        $message .= "In " . $this->_error->exception->getFile() . ", line: " . $this->_error->exception->getLine() . "<br/>";
        $message .= "At " . date("H:i:s Y-m-d") . "</h3><br/>";
        $message .= "Please notify your administrator with this information.<br/>";
        break;
      default:
        $message .= "Message: " . $this->_error->exception->getMessage() . "\n\n";
        $message .= "Trace:\n" . $this->_error->exception->getTraceAsString() . "\n\n";
      }
    return $message;
    }

  }
