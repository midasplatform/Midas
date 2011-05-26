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


class Helloworld_FeedCoreController extends Helloworld_AppController
{

  public $_models=array('User');
  public $_moduleModels=array('Hello');
  public $_daos=array('Item');
  public $_moduleDaos=array('Hello');
  public $_components=array('Utility');
  public $_moduleComponents=array('Hello');
  public $_forms=array('Install');
  public $_moduleForms=array('Index');
  
  /**
   * @method initAction()
   *  Index Action (first action when we access the application)
   */
  function init()
   {         
  
   } // end method indexAction

   function indexAction()
    {
    $this->callCoreAction();
    } 
    
}//end class