<?php
/** notification manager*/
class Keyfiles_Notification extends MIDAS_Notification
  {
  public $moduleName = 'keyfiles';

  /** Register callbacks */
  public function init()
    {
    $fc = Zend_Controller_Front::getInstance();
    $this->moduleWebroot = $fc->getBaseUrl().'/modules/'.$this->moduleName;
    $this->coreWebroot = $fc->getBaseUrl().'/core';

    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_ACTIONMENU', 'getItemMenuLink');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_JS', 'getItemViewJs');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_CSS', 'getItemViewCss');
    }

  /** Get the link to place in the item action menu */
  public function getItemMenuLink($params)
    {
    $webroot = Zend_Controller_Front::getInstance()->getBaseUrl();
    return '<li><a href="'.$webroot.'/'.$this->moduleName.'/download/item?itemId='.$params['item']->getKey().
           '"><img alt="" src="'.$webroot.'/core/public/images/icons/key.png" /> Download key files</a></li>';
    }

  /** Get javascript for the item view */
  public function getItemViewJs($params)
    {
    return array($this->moduleWebroot.'/public/js/item/keyfiles.item.view.js');
    }

  /** Get stylesheets for the item view */
  public function getItemViewCss($params)
    {
    return array($this->moduleWebroot.'/public/css/item/keyfiles.item.view.css');
    }
  } //end class
?>

