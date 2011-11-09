<?php

class Scheduler_Upgrade_1_0_3 extends MIDASUpgrade
{
  public function preUpgrade()
    {

    }

  public function mysql()
    {
    }


  public function postUpgrade()
    {
    $this->addTableField('scheduler_job', 'creator_id', 'bigint(20)', 'bigint', false);
    }
}
?>
