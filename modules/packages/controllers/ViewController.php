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

  public $_models = array('User', 'Bitstream', 'Item', 'Folder', 'Community', 'Folderpolicyuser', 'Folderpolicygroup');
  public $_moduleModels = array('Package', 'Extension');
  public $_daos = array('User', 'Bitstream', 'Item', 'Folder', 'Community');
  public $_moduleDaos = array('Package', 'Extension');
  public $_components = array('Date', 'Utility');
  public $_moduleComponents = array('Utility');
  public $_forms = array();
  public $_moduleForms = array();

  /** Helper function allowing to generate breadcrumb */
  private function _breadcrumb($subfolder = '', $name = '')
    {
    // TODO Generalize the concept of 'breadcrumb' for plugins ? Look at Zend BreadCrumb ?
    $breadcrumb  = '<link type="text/css" rel="stylesheet" href="'.$this->view->coreWebroot.'/public/css/common/common.browser.css" />';
    $breadcrumb .= '<link type="text/css" rel="stylesheet" href="'.$this->view->coreWebroot.'/public/css/folder/folder.view.css" />';
    $breadcrumb .= '<ul class="pathBrowser">';
    $breadcrumb .= '  <li class ="pathCommunity"><img alt = "" src = "'.$this->view->moduleWebroot.'/public/images/'.$this->moduleName.'.png" /><span><a href="'.$this->view->webroot.'/'.$this->moduleName.'/view">&nbsp;'.$this->view->moduleFullName.'</a></span></li>';
    if($subfolder != '')
      {
      if($name == '')
        {
        $name = $subfolder;
        }
      $breadcrumb .= '  <li class ="pathFolder"><img alt = "" src = "'.$this->view->moduleWebroot.'/public/images/'.$this->moduleName.'_'.$subfolder.'.png" /><span><a href="'.$this->view->webroot.'/'.$this->moduleName.'/view/'.$subfolder.'">&nbsp;'.$name.'</a></span></li>';
      }
    $breadcrumb .= '</ul>';
    return $breadcrumb;
    }

  

}//end class
