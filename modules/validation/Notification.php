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
class Validation_Notification extends MIDAS_Notification
  {
  public $_models=array();

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_API_METHODS', 'getWebApiMethods');
    }//end init

  /** get Config Tabs */
  public function getWebApiMethods()
    {
    $methods = array();
    $help = array();
    $help['params'] = array();
    $help['example'] = array();
    $help['return'] = 'foobar';
    $help['description'] = 'Incredibly simple test';
    $methods[] = array('name'=> 'test',
                       'help' => $help,
                       'callbackObject' => &$this,
                       'callbackFunction' => 'test');
    return $methods;
    }
    
  public function test()
    {
    return array('foo'=> 'BAR');
    }
  } //end class
  
?>