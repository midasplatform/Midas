<?php

class Upgrade_3_2_1 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

  public function mysql()
    {
    }

  public function pgsql()
    {
    }

  public function postUpgrade()
    {
    $user = new Zend_Session_Namespace('Auth_User');
    $id = $user && $user->Dao ? $user->Dao->getKey() : '1';

    $modelLoader = new MIDAS_ModelLoader();
    $settingModel = $modelLoader->loadModel('Setting');
    $settingModel->setConfig('adminuser', $id);
    }
}
?>
