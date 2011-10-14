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

/**
 *  Batchmake_IndexController
 */
class Batchmake_IndexController extends Batchmake_AppController
{

  /**
   * @method indexAction(), will display the index page of the batchmake module.
   */
  public function indexAction()
    {
    $this->view->header = $this->t("BatchMake Server Side Processing");
    }



}//end class
