<?php
/** notification manager*/
class Metadataextractor_Notification extends MIDAS_Notification
  {
  public $_moduleComponents=array('Extractor');
  public $moduleName = 'metadataextractor';
  
  /** init notification process*/
  public function init()
    {
    $this->addTask("TASK_METADATAEXTRACTOR_EXTRACT", 'extractMetaData', "Extract Metadata. Parameters: Item, Revision");
    $this->addEvent('EVENT_CORE_UPLOAD_FILE', 'TASK_METADATAEXTRACTOR_EXTRACT');
    }//end init
    
  /** get Config Tabs */
  public function extractMetaData($params)
    {
    $this->ModuleComponent->Extractor->extract($params[1]);
    return;
    }
  } //end class
?>