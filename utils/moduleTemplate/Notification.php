<?php
/** notification manager*/
class @MN_CAP@_Notification extends MIDAS_Notification
  {
  public $moduleName = '@MN@';

  /** init notification process */
  public function init()
    {
    $fc = Zend_Controller_Front::getInstance();
    $this->moduleWebroot = $fc->getBaseUrl().'/modules/'.$this->moduleName;
    $this->coreWebroot = $fc->getBaseUrl().'/core';

    $this->addCallBack('CALLBACK_CORE_ITEM_DELETED', 'handleItemDeleted');
    }

  /**
   * STUB: example of receiving a callback when an item is deleted
   */
  public function handleItemDeleted($params)
    {
    $itemDao = $params['item'];
    // TODO do something about this item dao
    }
  }
?>
