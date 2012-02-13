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
    $this->coreWebroot = $fc->getBaseUrl().'/core';

    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_JS', 'getJs');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_CSS', 'getCss');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_JSON', 'getJson');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_INFO', 'getItemInfo');
    $this->addCallBack('CALLBACK_CORE_ITEM_VIEW_APPEND_ELEMENTS', 'getElement');
    }

  /** Some html to be appended to the item view sidebar */
  public function getItemInfo($params)
    {
    return '<div class="sideElement" id="sideElementRatings">
              <h1>Rating Distribution</h1>
            </div>';
    }

  /** Get javascript for the ratings */
  public function getJs($params)
    {
    return array($this->moduleWebroot.'/public/js/star_rating/jquery.ui.stars.min.js',
                 $this->moduleWebroot.'/public/js/item/item.ratings.js',
                 $this->coreWebroot.'/public/js/jquery/jquery.jqplot.min.js',
                 $this->coreWebroot.'/public/js/jquery/jqplot/jqplot.barRenderer.min.js',
                 $this->coreWebroot.'/public/js/jquery/jqplot/jqplot.categoryAxisRenderer.min.js',
                 $this->coreWebroot.'/public/js/jquery/jqplot/jqplot.pointLabels.min.js');
    }

  /** Get stylesheets for the ratings */
  public function getCss($params)
    {
    return array($this->moduleWebroot.'/public/css/star_rating/jquery.ui.stars.css',
                 $this->moduleWebroot.'/public/css/item/item.ratings.css',
                 $this->coreWebroot.'/public/css/jquery/jquery.jqplot.css');
    }

  /** Get the element to render at the bottom of the item view */
  public function getElement($params)
    {
    return array('rating');
    }

  /** Get json to pass to the view */
  public function getJson($params)
    {
    $modelLoader = new MIDAS_ModelLoader();
    $itemRatingModel = $modelLoader->loadModel('Itemrating', $this->moduleName);
    $data = $itemRatingModel->getAggregateInfo($params['item']);
    if($this->userSession->Dao)
      {
      $data['userRating'] = $itemRatingModel->getByUser($this->userSession->Dao, $params['item']);
      }
    return $data;
    }
  }
?>

