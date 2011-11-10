<?php

class Scheduler_Upgrade_1_0_3 extends MIDASUpgrade
{
  public function preUpgrade()
    {

    }

  public function mysql()
    {
    $sql = "ALTER TABLE scheduler_job ADD creator_id bigint(20)";
    $this->db->query($sql);
    }


  public function postUpgrade()
    {
    }
}
?>
