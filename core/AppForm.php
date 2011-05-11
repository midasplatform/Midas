<?php
/** Common Form Class*/
class AppForm
  {
  /** constructor*/
  public function  __construct()
    {
    $fc = Zend_Controller_Front::getInstance();
    $this->webroot = $fc->getBaseUrl();
    }//end construct


  /** translation  */
  protected  function t($text)
    {
    Zend_Loader::loadClass("InternationalizationComponent", BASE_PATH.'/core/controllers/components');
    return InternationalizationComponent::translate($text);
    }//en method t
  }//end class
