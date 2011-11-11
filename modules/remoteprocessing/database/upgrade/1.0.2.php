<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

class Remoteprocessing_Upgrade_1_0_2 extends MIDASUpgrade
{
  public function preUpgrade()
    {

    }

  public function mysql()
    {
    $sql = "ALTER TABLE remoteprocessing_job ADD name varchar(512)";
    $this->db->query($sql);
    }

  public function pgsql()
    {

    }

  public function postUpgrade()
    {
    }
}
?>
