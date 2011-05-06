<?php
class  Zend_View_Helper_Slicename
{
  /** translation helper */
    function slicename($name, $nchar)
      {
      Zend_Loader::loadClass('UtilityComponent', BASE_PATH . '/core/controllers/components');
      $component = new UtilityComponent();
      return $component->sliceName($name, $nchar);
      }


    /** Set view*/
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }
}// end class