<?php
/** notification manager*/
class Visualize_Notification extends MIDAS_Notification
  {
  public $_moduleComponents=array('Main');
  public $moduleName='visualize';
  /** init notification process*/
  public function init($type, $params)
    {
    switch ($type)
      {
      case MIDAS_NOTIFY_CAN_VISUALIZE:
        return $this->ModuleComponent->Main->canVisualizeWithParaview($params['item']);
        break;

      default:
        break;
      }
    }//end init  
  } //end class
?>