<?php

/**
 * Upgrade 3.2.10 adds newuserinvite table
 */
class Upgrade_3_2_10 extends MIDASUpgrade
{

  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $this->db->query("CREATE TABLE `newuserinvitation` (
      `newuserinvitation_id` bigint(20) NOT NULL AUTO_INCREMENT,
      `auth_key` varchar(255) NOT NULL,
      `email` varchar(255) NOT NULL,
      `inviter_id` bigint(20) NOT NULL,
      `community_id` bigint(20) NOT NULL,
      `group_id` bigint(20) NOT NULL,
      `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`newuserinvitation_id`)
      )");
    $this->db->query("CREATE TABLE `pendinguser` (
      `pendinguser_id` bigint(20) NOT NULL AUTO_INCREMENT,
      `auth_key` varchar(255) NOT NULL,
      `email` varchar(255) NOT NULL,
      `password` varchar(100) NOT NULL,
      `firstname` varchar(255) NOT NULL,
      `lastname` varchar(255) NOT NULL,
      `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`pendinguser_id`)
      )");
    $this->db->query("ALTER TABLE `communityinvitation` ADD COLUMN `group_id` bigint(20) NULL DEFAULT NULL");
    }

  public function pgsql()
    {
    $this->db->query("CREATE TABLE newuserinvitation (
      newuserinvitation_id serial PRIMARY KEY,
      auth_key character varying(255) NOT NULL,
      email character varying(255) NOT NULL,
      inviter_id bigint NOT NULL,
      community_id bigint NOT NULL,
      group_id bigint NOT NULL,
      date_creation timestamp without time zone NOT NULL DEFAULT now()
      )");
    $this->db->query("CREATE TABLE pendinguser (
      pendinguser_id serial PRIMARY KEY,
      auth_key character varying(255) NOT NULL,
      email character varying(255) NOT NULL,
      password character varying(100) NOT NULL,
      firstname character varying(255) NOT NULL,
      lastname character varying(255) NOT NULL,
      date_creation timestamp without time zone NOT NULL DEFAULT now()
      )");
    $this->db->query("ALTER TABLE communityinvitation ADD COLUMN group_id bigint NULL DEFAULT NULL");
    }

  public function postUpgrade()
    {
    }

}
?>
