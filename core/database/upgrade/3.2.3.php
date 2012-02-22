<?php

/**
 * Adds an index on bitstream.checksum for faster collision checking across
 * multiple assetstores
 */
class Upgrade_3_2_3 extends MIDASUpgrade
{
  public function preUpgrade()
    {
    }

  public function mysql()
    {
    $this->db->query("ALTER TABLE `bitstream` ADD INDEX (`checksum`)");
    }

  public function pgsql()
    {
    $this->db->query("CREATE INDEX bitstream_idx_checksum ON bitstream (checksum)");
    }

  public function postUpgrade()
    {
    }
}
?>
