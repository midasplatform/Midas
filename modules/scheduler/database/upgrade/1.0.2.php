<?php

class Scheduler_Upgrade_1_0_2 extends MIDASUpgrade
{
  public function preUpgrade()
    {

    }

  public function mysql()
    {
    $sql = "DELETE FROM scheduler_job_log";
    $this->db->query($sql);
    $sql = "ALTER TABLE scheduler_job_log MODIFY log_id bigint(20) NOT NULL AUTO_INCREMENT";
    $this->db->query($sql);

    }


  public function postUpgrade()
    {

    }
}
?>
