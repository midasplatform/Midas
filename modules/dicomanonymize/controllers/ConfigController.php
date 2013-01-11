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

/** Module configuration controller (for admin use) */
class Dicomanonymize_ConfigController extends Dicomanonymize_AppController
{
  public $_components = array('Breadcrumb');

  /**
   * Configuration view
   */
  function indexAction()
    {
    $this->requireAdminPrivileges();
    //$this->view->repoBrowserUrl = $this->Setting->getValueByName('repoBrowserUrl', $this->moduleName);

    $breadcrumbs = array();
    $breadcrumbs[] = array('type' => 'moduleList');
    $breadcrumbs[] = array('type' => 'custom',
                           'text' => 'DICOM Anonymizer Configuration',
                           'icon' => $this->view->moduleWebroot.'/public/images/picture_delete.png');
    $this->Component->Breadcrumb->setBreadcrumbHeader($breadcrumbs, $this->view);
    }

  /**
   * Submit configuration settings
   */
  function submitAction()
    {
    $this->requireAdminPrivileges();
    $this->disableLayout();
    $this->disableView();

    $repoBrowserUrl = $this->_getParam('repoBrowserUrl');
    //$this->Setting->setConfig('repoBrowserUrl',  $repoBrowserUrl, $this->moduleName);

    echo JsonComponent::encode(array('message' => 'Changes saved', 'status' => 'ok'));
    }

}//end class
