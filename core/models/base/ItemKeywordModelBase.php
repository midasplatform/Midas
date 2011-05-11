<?php
/** ItemKeywordModelBase */
class ItemKeywordModelBase extends AppModel
{
  /** Contructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'itemkeyword';
    $this->_daoName = 'ItemKeywordDao';
    $this->_key = 'keyword_id';

    $this->_mainData = array(
      'keyword_id' => array('type' => MIDAS_DATA),
      'value' => array('type' => MIDAS_DATA),
      'relevance' => array('type' => MIDAS_DATA),
      );
    $this->initialize(); // required
    } // end __construct()  
  

} // end class ItemKeywordModelBase