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

require_once BASE_PATH.'/modules/api/library/APIEnabledNotification.php';

class Packages_Notification extends ApiEnabled_Notification
  {
  public $moduleName = 'packages';
  public $_moduleComponents = array('Api');
  public $_models = array();

  /** init notification process*/
  public function init()
    {
    $this->addCallBack('CALLBACK_CORE_GET_LEFT_LINKS', 'getLeftLinks');
    $this->addCallBack('CALLBACK_CORE_ITEM_DELETED', 'itemDeleted');
    //TODO $this->addCallBack('CALLBACK_CORE_COMMUNITY_DELETED', 'communityDeleted');
    $this->enableWebAPI($this->moduleName);
    }//end init

  /**
   * Add the link to this module to the left side list
   */
  public function getLeftLinks()
    {
    $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
    $moduleWebroot = $baseUrl.'/'.$this->moduleName;
    // TODO iterate over all projects
    return array('<Project> Packages' => array(
      $moduleWebroot.'/view',
      $baseUrl.'/modules/'.$this->moduleName.'/public/images/package.png'));
    }

  /**
   * When an item is deleted, we must delete associated package/extension records
   */
  public function itemDeleted($args)
    {
    $itemDao = $args['item'];
    $modelLoader = new MIDAS_ModelLoader();

    $packageModel = $modelLoader->loadModel('Package', $this->moduleName);
    $package = $packageModel->getByItemId($itemDao->getKey());
    if($package)
      {
      $packageModel->delete($package);
      }

    $extensionModel = $modelLoader->loadModel('Extension', $this->moduleName);
    $extension = $extensionModel->getByItemId($itemDao->getKey());
    if($extension)
      {
      $extensionModel->delete($extension);
      }
    }

  /**
   * When a community is deleted, we must delete its associated project
   */
  public function communityDeleted($args)
    {
    // TODO
    }

  } //end class

?>
