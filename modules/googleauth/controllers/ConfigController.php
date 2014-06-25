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
 * This controller is used to manage global configuration of the tango
 * dashboard module.
 */
class Googleauth_ConfigController extends Googleauth_AppController
{
  public $_models = array('Setting');

  /** Renders the config view */
  function indexAction()
    {
    $this->requireAdminPrivileges();

    $this->view->clientId = $this->Setting->getValueByName('client_id', $this->moduleName);
    $this->view->clientSecret = $this->Setting->getValueByName('client_secret', $this->moduleName);
    }


  /** XHR endpoint to save config values */
  public function submitAction()
    {
    $this->requireAdminPrivileges();

    $this->disableLayout();
    $this->disableView();

    $params = array('client_id', 'client_secret');
    foreach($params as $param)
      {
      $value = $this->_getParam($param);
      $this->Setting->setConfig($param, $value, $this->moduleName);
      }

    echo JsonComponent::encode(array(true, 'Changes saved'));
    }

}//end class
