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
/** packages view controller*/
class Packages_ViewController extends Packages_AppController
{
  public $_models = array();
  public $_moduleModels = array();

  /**
   * View for the Packages tab within the community view.
   * Shows a list of all applications and their links
   */
  public function projectAction()
    {
    $this->disableLayout();
    $this->disableView();
    echo 'hello world';
    }
}//end class
