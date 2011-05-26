<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

class Zend_View_Helper_T
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