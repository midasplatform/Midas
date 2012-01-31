<?php

class Upgrade_3_2_1 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $sql = "ALTER TABLE condor_dag ADD COLUMN dag_filename text NOT NULL;";
    $this->db->query($sql);
    $sql = "ALTER TABLE condor_job ADD COLUMN post_filename text NOT NULL;";
    $this->db->query($sql);
    }

  public function pgsql()
    {
    $sql = "ALTER TABLE condor_dag ADD COLUMN dag_filename text NOT NULL;";
    $this->db->query($sql);
    $sql = "ALTER TABLE condor_job ADD COLUMN post_filename text NOT NULL;";
    $this->db->query($sql);
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
