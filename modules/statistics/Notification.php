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
/** notification manager*/
class Statistics_Notification extends MIDAS_Notification
  {
  public $moduleName = 'statistics';
  public $_moduleModels = array('Download');
  public $_moduleComponents = array('Report');

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_FOOTER_LAYOUT', 'getFooter');
    $this->addCallBack('CALLBACK_CORE_GET_USER_MENU', 'getUserMenu');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_ACTIONMENU', 'getItemMenuLink');
    $this->addCallBack('CALLBACK_CORE_PLUS_ONE_DOWNLOAD', 'addDownload');

    $this->addTask('TASK_STATISTICS_SEND_REPORT', 'sendReport', 'Send a daily report');
    }//end init

  /** send the batch report to admins */
  public function sendReport()
    {
    echo $this->ModuleComponent->Report->generate();
    $this->ModuleComponent->Report->send();
    }

  /** add download stat*/
  public function addDownload($params)
    {
    $item = $params['item'];
    $user = $this->userSession->Dao;
    $this->Statistics_Download->addDownload($item, $user);
    }

  /** user Menu link */
  public function getUserMenu()
    {
    if($this->logged && $this->userSession->Dao->getAdmin() == 1)
      {
      $fc = Zend_Controller_Front::getInstance();
      $moduleWebroot = $fc->getBaseUrl().'/statistics';
      return array($this->t('Statistics') => $moduleWebroot);
      }
    else
      {
      return null;
      }
    }

  /** Get the link to place in the item action menu */
  public function getItemMenuLink($params)
    {
    $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
    return '<li><a href="'.$webroot.'/'.$this->moduleName.'/item?id='.$params['item']->getKey().
           '"><img alt="" src="'.$webroot.'/modules/'.$this->moduleName.
           '/public/images/chart_bar.png" /> '.$this->t('Statistics').'</a></li>';
    }

  /** get layout footer */
  public function getFooter()
    {
    $modulesConfig = Zend_Registry::get('configsModules');
    $url = $modulesConfig['statistics']->piwik->url;
    $id = $modulesConfig['statistics']->piwik->id;
    return "
      <!-- Piwik -->
      <script type=\"text/javascript\">
      var pkBaseURL = '".$url."/';
      document.write(unescape(\"%3Cscript src='\" + pkBaseURL + \"piwik.js' type='text/javascript'%3E%3C/script%3E\"));
      </script><script type=\"text/javascript\">
      try {
      var piwikTracker = Piwik.getTracker(pkBaseURL + \"piwik.php\", 1);
      piwikTracker.trackPageView();
      piwikTracker.enableLinkTracking();
      } catch( err ) {}
      </script><noscript><p><img src=\"".$url."/piwik.php?idsite=".$id."\" style=\"border:0\" alt=\"\" /></p></noscript>
      <!-- End Piwik Tracking Code -->
      ";
    }
  } //end class
?>

