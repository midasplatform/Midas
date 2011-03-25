<?php
class  Zend_View_Helper_Hello
{

    function hello()
    {
    return "helloHelper";
    }//en method t



    /** Set view*/
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }
}// end class