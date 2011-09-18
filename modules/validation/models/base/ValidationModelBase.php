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

/** demo base model*/
class Validation_ValidationModelBase extends Validation_AppModel
{
  /** constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'validation_validation';
    $this->_key = 'validation_id';

    $this->_mainData = array(
        'validation_id' =>  array('type' => MIDAS_DATA),
        );
    $this->initialize(); // required
    } // end __construct()
    
} // end class Helloworld_HelloModelBase
