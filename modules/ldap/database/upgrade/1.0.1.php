<?php
/**
 * Add the ldap_user table for storing ldap users
 */
class Ldap_Upgrade_1_0_1 extends MIDASUpgrade
  {
  public function preUpgrade()
    {
    }

  /** Mysql upgrade */
  public function mysql()
    {
    $this->db->query("CREATE TABLE IF NOT EXISTS `ldap_user` (
                      `ldap_user_id` bigint(20) NOT NULL AUTO_INCREMENT,
                      `user_id` bigint(20) NOT NULL,
                      `login` varchar(255) NOT NULL,
                      PRIMARY KEY (`ldap_user_id`),
                      KEY `login` (`login`)
                      )");
    }

  /** Pgsql upgrade */
  public function pgsql()
    {
    $this->db->query("CREATE TABLE ldap_user (
                      ldap_user_id serial PRIMARY KEY,
                      user_id bigint NOT NULL,
                      login character varying(255) NOT NULL)");

    $this->db->query("CREATE INDEX ldap_user_login_idx ON ldap_user (login)");
    }

  public function postUpgrade()
    {
    }
  }
