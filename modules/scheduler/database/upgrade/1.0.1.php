<?php

class Scheduler_Upgrade_1_0_1 extends MIDASUpgrade
{
  public function preUpgrade()
    {

    }

  public function mysql()
    {
    $sql = "CREATE TABLE IF NOT EXISTS `scheduler_job_log` (
        `log_id` bigint(20) NOT NULL,
        `job_id` bigint(20),
        `date` timestamp,
        `log` text,
        PRIMARY KEY (`log_id`)
      ) DEFAULT CHARSET=utf8";
    $this->db->query($sql);

    }


  public function postUpgrade()
    {

    }
}
?>
