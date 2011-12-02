<?php
/** notification manager*/
class Statistics_Notification extends MIDAS_Notification
  {
  public $moduleName = 'statistics';
  public $_moduleModels = array('Download', 'IpLocation');
  public $_moduleComponents = array('Report');

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_FOOTER_LAYOUT', 'getFooter');
    $this->addCallBack('CALLBACK_CORE_GET_USER_MENU', 'getUserMenu');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_ACTIONMENU', 'getItemMenuLink');
    $this->addCallBack('CALLBACK_CORE_PLUS_ONE_DOWNLOAD', 'addDownload');

    $this->addTask('TASK_STATISTICS_SEND_REPORT', 'sendReport', 'Send a daily report');
    $this->addTask('TASK_STATISTICS_PERFORM_GEOLOCATION', 'performGeolocation', 'Perform geolocation based on IP');
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

  /** perform download geolocation by ip address */
  public function performGeolocation()
    {
    $this->Statistics_IpLocation->performGeolocation();
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

