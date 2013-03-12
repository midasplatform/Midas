<?php

/**
 * Upgrade 3.2.12 improves the password salting and hashing system
 */
class Upgrade_3_2_12 extends MIDASUpgrade
{

  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $this->db->query("ALTER TABLE `user` ADD COLUMN `hash_alg` varchar(32) NOT NULL default ''");
    $this->db->query("ALTER TABLE `user` ADD COLUMN `salt` varchar(64) NOT NULL default ''");
    $this->db->query("ALTER TABLE `pendinguser` ADD COLUMN `salt` varchar(64) NOT NULL default ''");

    $this->db->query("CREATE TABLE `password` (
                        `hash` varchar(128) NOT NULL,
                        PRIMARY KEY (`hash`)
                     ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    $this->_movePasswords();

    $this->db->query("ALTER TABLE `user` DROP `password`");
    $this->db->query("ALTER TABLE `pendinguser` DROP `password`");
    }

  public function pgsql()
    {
    $this->db->query("ALTER TABLE \"user\" ADD COLUMN hash_alg character varying(32) NOT NULL DEFAULT ''");
    $this->db->query("ALTER TABLE \"user\" ADD COLUMN salt character varying(64) NOT NULL DEFAULT ''");
    $this->db->query("ALTER TABLE \"pendinguser\" ADD COLUMN salt character varying(64) NOT NULL DEFAULT ''");

    $this->db->query("CREATE TABLE password (
                        hash character varying(128) NOT NULL,
                        CONSTRAINT password_hash PRIMARY KEY (hash)
                     )");
    $this->_movePasswords();

    // In pgsql we must explicitly sort the rows by using the cluster command
    $this->db->query("CLUSTER password USING password_hash");

    $this->db->query("ALTER TABLE \"user\" DROP COLUMN password");
    $this->db->query("ALTER TABLE \"pendinguser\" DROP COLUMN password");
    }

  public function postUpgrade()
    {
    }

  /**
   * Moves passwords from the user table to the new password hash table
   */
  private function _movePasswords()
    {
    // Move hashes from user table to password table
    $sql = $this->db->select()
            ->from(array('user'), array('password'))
            ->distinct();
    $rows = $this->db->fetchAll($sql);
    foreach($rows as $row)
      {
      $this->db->insert('password', array('hash' => $row['password']));
      }
    // Set the salt and hash alg to the appropriate value to denote a legacy user
    $this->db->update('user', array('hash_alg' => 'md5', 'salt' => ''));
    }
}
?>
