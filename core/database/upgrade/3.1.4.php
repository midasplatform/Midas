<?php

class Upgrade_3_1_4 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $sql = "CREATE TABLE IF NOT EXISTS `setting` (
            `setting_id` bigint(20) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `module`  varchar(255) NOT NULL,
            `value` text NULL DEFAULT NULL ,
            PRIMARY KEY (`setting_id`)
            )  DEFAULT CHARSET=utf8;";
    $this->db->query($sql);
    }


  public function pgsql()
    {
    $sql = "CREATE TABLE  setting (
              setting_id serial  PRIMARY KEY,
              name  character varying(256) NOT NULL,
              module  character varying(256) NOT NULL,
              value text NOT NULL
             )  ;";
    $this->db->query($sql);
    }

  public function postUpgrade()
    {
    }
}
?>