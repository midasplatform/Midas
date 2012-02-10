<?php
/** notification manager*/
class Ratings_Notification extends MIDAS_Notification
  {
  public $moduleName = 'ratings';
  public $moduleWebroot = '';

  /** init notification process */
  public function init()
    {
    $fc = Zend_Controller_Front::getInstance();
    $this->moduleWebroot = $fc->getBaseUrl().'/modules/'.$this->moduleName;

    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_JS', 'getJs');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_CSS', 'getCss');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_APPEND_ELEMENTS', 'getElement');
    }

  /** Get javascript for the ratings */
  public function getJs($params)
    {
    return array($this->moduleWebroot.'/public/js/star_rating/jquery.ui.stars.min.js',
                 $this->moduleWebroot.'/public/js/item/item.ratings.js');
    }

  /** Get stylesheets for the ratings */
  public function getCss($params)
    {
    return array($this->moduleWebroot.'/public/css/star_rating/jquery.ui.stars.css',
                 $this->moduleWebroot.'/public/css/item/item.ratings.css');
    }

  /** Get the element to render at the bottom of the item view */
  public function getElement($params)
    {
    return array('rating');
    }
  }
?>

