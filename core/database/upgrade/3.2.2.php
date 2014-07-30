<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

// The old constants file is removed now; copied its contents here for the upgrade
define("LICENSE_PDDL", 0);
define("LICENSE_ODC_BY", 1);
define("LICENSE_ODBL", 2);
define("LICENSE_RESERVED", 3);
define("LICENSE_CC_ATTRIBUTION", 4);
define("LICENSE_CC_ATTRIBUTION_SHAREALIKE", 5);
define("LICENSE_CC_NODERIVS", 6);
define("LICENSE_CC_NONCOMMERCIAL", 7);
define("LICENSE_CC_NONCOMMERCIAL_SHARELIKE", 8);
define("LICENSE_CC_NONCOMMERCIAL_NODERIVS", 9);

class Upgrade_3_2_2 extends MIDASUpgrade
  {
  var $existingLicenses;

  public function preUpgrade()
    {
    $this->existingLicenses = array(
      array('constant' => LICENSE_PDDL,
            'name' => 'Public (PDDL)',
            'fulltext' => '<b>You are free:</b><ul>'.
                          '<li>To Share: To copy, distribute and use the database.</li>'.
                          '<li>To Create: To produce works from the database.</li>'.
                          '<li>To Adapt: To modify, transform, and build upon the database.</li></ul>'.
                          '<a href="http://opendatacommons.org/licenses/pddl/summary">Full License Information</a>'),
      array('constant' => LICENSE_ODC_BY,
            'name' => 'Public: Attribution (ODC-BY)',
            'fulltext' => '<b>You are free:</b><ul>'.
                          '<li>To Share: To copy, distribute and use the database.</li>'.
                          '<li>To Create: To produce works from the database.</li>'.
                          '<li>To Adapt: To modify, transform, and build upon the database.</li></ul>'.
                          '<b>As long as you:</b><ul>'.
                          '<li>Attribute: You must attribute any public use of the database, or works produced from the database, '.
                          'in the manner specified in the license. For any use or redistribution of the database, or works produced '.
                          'from it, you must make clear to others the license of the database and keep intact any notices on the '.
                          'original database.</li></ul>'.
                          '<a href="http://opendatacommons.org/licenses/by/summary">Full License Information</a>'),
      array('constant' => LICENSE_ODBL,
            'name' => 'Public: Attribution, Share-Alike (ODBL)',
            'fulltext' => '<b>You are free:</b><ul>'.
                          '<li>To Share: To copy, distribute and use the database.</li>'.
                          '<li>To Create: To produce works from the database.</li>'.
                          '<li>To Adapt: To modify, transform, and build upon the database.</li></ul>'.
                          '<b>As long as you:</b><ul>'.
                          '<li>Attribute: You must attribute any public use of the database, or works produced from the database, '.
                          'in the manner specified in the license. For any use or redistribution of the database, or works produced '.
                          'from it, you must make clear to others the license of the database and keep intact any notices on the '.
                          'original database.</li>'.
                          '<li>Share-Alike: If you publicly use any adapted version of this database, or works produced from an adapted '.
                          'database, you must also offer that adapted database under the ODbL.</li>'.
                          '<li>Keep open: If you redistribute the database, or an adapted version of it, then you may use technological '.
                          'measures that restrict the work (such as DRM) as long as you also redistribute a version without such measures.</li></ul>'.
                          '<a href="http://opendatacommons.org/licenses/odbl/summary">Full License Information</a>'),
      array('constant' => LICENSE_RESERVED,
            'name' => 'Private: All right reserved',
            'fulltext' => 'This work is copyrighted by its owner and cannot be shared, distributed or modified without prior consent of the author.'),
      array('constant' => LICENSE_CC_ATTRIBUTION,
            'name' => 'Public: Attribution (CC BY 3.0)',
            'fulltext' => '<b>You are free:</b><ul>'.
                          '<li>To Share: To copy, distribute and transmit the work.</li>'.
                          '<li>To Remix: To adapt the work.</li>'.
                          '<li>To make commercial use of the work.</li></ul>'.
                          '<b>Under the following conditions:</b><ul>'.
                          '<li>Attribution: You must attribute the work in the manner specified by the author or licensor '.
                          '(but not in any way that suggests that they endorse you or your use of the work)</li></ul>'.
                          '<a href="http://creativecommons.org/licenses/by/3.0/">Full License Information</a>'),
      array('constant' => LICENSE_CC_ATTRIBUTION_SHAREALIKE,
            'name' => 'Public: Attribution, Share-Alike (CC BY-SA 3.0)',
            'fulltext' => '<b>You are free:</b><ul>'.
                          '<li>To Share: To copy, distribute and transmit the work.</li>'.
                          '<li>To Remix: To adapt the work.</li>'.
                          '<li>To make commercial use of the work.</li></ul>'.
                          '<b>Under the following conditions:</b><ul>'.
                          '<li>Attribution: You must attribute the work in the manner specified by the author or licensor '.
                          '(but not in any way that suggests that they endorse you or your use of the work)</li>'.
                          '<li>Share Alike: If you alter, transform, or build upon this work, you may distribute the resulting '.
                          'work only under the same or similar license to this one.</li></ul>'.
                          '<a href="http://creativecommons.org/licenses/by-sa/3.0/">Full License Information</a>'),
      array('constant' => LICENSE_CC_NODERIVS,
            'name' => 'Public: Attribution, No Derivative Works (CC BY-ND 3.0)',
            'fulltext' => '<b>You are free:</b><ul>'.
                          '<li>To Share: To copy, distribute and transmit the work.</li>'.
                          '<li>To make commercial use of the work.</li></ul>'.
                          '<b>Under the following conditions:</b><ul>'.
                          '<li>Attribution: You must attribute the work in the manner specified by the author or licensor '.
                          '(but not in any way that suggests that they endorse you or your use of the work)</li>'.
                          '<li>No Derivative Works: You may not alter, transform, or build upon this work.</li></ul>'.
                          '<a href="http://creativecommons.org/licenses/by-nd/3.0/">Full License Information</a>'),
      array('constant' => LICENSE_CC_NONCOMMERCIAL,
            'name' => 'Public: Attribution, Non Commercial (CC BY-NC 3.0)',
            'fulltext' => '<b>You are free:</b><ul>'.
                          '<li>To Share: To copy, distribute and transmit the work.</li>'.
                          '<li>To Remix: To adapt the work.</li></ul>'.
                          '<b>Under the following conditions:</b><ul>'.
                          '<li>Attribution: You must attribute the work in the manner specified by the author or licensor '.
                          '(but not in any way that suggests that they endorse you or your use of the work)</li>'.
                          '<li>Noncommercial: You may not use this work for commercial purposes.</li></ul>'.
                          '<a href="http://creativecommons.org/licenses/by-nc/3.0/">Full License Information</a>'),
      array('constant' => LICENSE_CC_NONCOMMERCIAL_SHARELIKE,
            'name' => 'Public: Attribution, Non Commercial, Share-Alike (CC BY-NC-SA 3.0)',
            'fulltext' => '<b>You are free:</b><ul>'.
                          '<li>To Share: To copy, distribute and transmit the work.</li>'.
                          '<li>To Remix: To adapt the work.</li></ul>'.
                          '<b>Under the following conditions:</b><ul>'.
                          '<li>Attribution: You must attribute the work in the manner specified by the author or licensor '.
                          '(but not in any way that suggests that they endorse you or your use of the work)</li>'.
                          '<li>Noncommercial: You may not use this work for commercial purposes.</li>'.
                          '<li>Share Alike: If you alter, transform, or build upon this work, you may distribute the resulting '.
                          'work only under the same or similar license to this one.</li></ul>'.
                          '<a href="http://creativecommons.org/licenses/by-nc-sa/3.0/">Full License Information</a>'),
      array('constant' => LICENSE_CC_NONCOMMERCIAL_NODERIVS,
            'name' => 'Public: Attribution, Non Commercial, No Derivative Works (CC BY-NC-ND 3.0)',
            'fulltext' => '<b>You are free:</b><ul>'.
                          '<li>To Share: To copy, distribute and transmit the work.</li></ul>'.
                          '<b>Under the following conditions:</b><ul>'.
                          '<li>Attribution: You must attribute the work in the manner specified by the author or licensor '.
                          '(but not in any way that suggests that they endorse you or your use of the work)</li>'.
                          '<li>Noncommercial: You may not use this work for commercial purposes.</li>'.
                          '<li>No Derivative Works: You may not alter, transform, or build upon this work.</li></ul>'.
                          '<a href="http://creativecommons.org/licenses/by-nc-nd/3.0/">Full License Information</a>')
      );
    }

  /**
   * Create the license table. Add our default license set to it.
   * Replace old reference column with a new one pointing at the license table entries.
   */
  public function mysql()
    {
    // Create the license table
    $this->db->query("CREATE TABLE IF NOT EXISTS `license` (
      `license_id` bigint(20) NOT NULL AUTO_INCREMENT,
      `name` TEXT NOT NULL,
      `fulltext` TEXT NOT NULL,
      PRIMARY KEY (`license_id`)
      )");

    // Add a logical foreign key for license into the itemrevision table. Can be nullable for no license
    $this->db->query("ALTER TABLE `itemrevision` ADD COLUMN `license_id` bigint(20) NULL");

    // Add existing licenses to the database
    foreach($this->existingLicenses as $value)
      {
      $this->db->insert('license', array('name' => $value['name'], 'fulltext' => $value['fulltext']));
      $id = $this->db->lastInsertId('license', 'license_id');

      // Update existing license references to point to our new table
      $this->db->update('itemrevision',
                        array('license_id' => $id),
                        array('license = ?' => $value['constant']));
      }

    // Remove the obsolete column from the item revision table
    $this->db->query("ALTER TABLE `itemrevision` DROP `license`");
    }

  public function pgsql()
    {
    // Create the license table
    $this->db->query("CREATE TABLE license (
      license_id serial PRIMARY KEY,
      name TEXT NOT NULL DEFAULT '',
      fulltext TEXT NOT NULL DEFAULT ''
      )");

    // Add a logical foreign key for license into the itemrevision table. Can be nullable for no license
    $this->db->query("ALTER TABLE itemrevision ADD COLUMN license_id bigint NULL");

    // Add existing licenses to the database
    foreach($this->existingLicenses as $value)
      {
      $this->db->insert('license', array('name' => $value['name'], 'fulltext' => $value['fulltext']));
      $id = $this->db->lastInsertId('license', 'license_id');

      // Update existing license references to point to our new table
      $this->db->update('itemrevision',
                        array('license_id' => $id),
                        array('license = ?' => $value['constant']));
      }

    // Remove the obsolete column from the item revision table
    $this->db->query("ALTER TABLE itemrevision DROP COLUMN license");
    }
  }
