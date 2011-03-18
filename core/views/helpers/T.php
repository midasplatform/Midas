<?php
class  Zend_View_Helper_T
{
  /** translation helper */
    function t($text)
    {
    Zend_Loader::loadClass("InternationalizationComponent",BASE_PATH.'/core/controllers/components');
    return InternationalizationComponent::translate($text);
    }//en method t



    /** Set view*/
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }
}// end class