<?php
class  Zend_View_Helper_Dateago
{
  /** translation helper */
    function dateago($timestamp)
    {
    Zend_Loader::loadClass('DateComponent', BASE_PATH . '/application/controllers/components');
    $component=new DateComponent();
    return $component->ago($timestamp);
    }
    

    /** Set view*/
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }
}// end class