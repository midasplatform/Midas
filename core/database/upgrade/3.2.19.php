<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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

/** Upgrade the core to version 3.2.19. */
class Upgrade_3_2_19 extends MIDASUpgrade
{
    protected $_licenses = array(
        'Public (PDDL)' => '**You are free:**\n\n* To Share: To copy, distribute and use the database.\n* To Create: To produce works from the database.\n* To Adapt: To modify, transform, and build upon the database.\n\n[Full License Information](http://opendatacommons.org/licenses/pddl/summary)',
        'Public: Attribution (ODC-BY)' => '**You are free:**\n\n* To Share: To copy, distribute and use the database.\n* To Create: To produce works from the database.\n* To Adapt: To modify, transform, and build upon the database.\n\n**As long as you:**\n\n* Attribute: You must attribute any public use of the database, or works produced from the database, in the manner specified in the license. For any use or redistribution of the database, or works produced from it, you must make clear to others the license of the database and keep intact any notices on the original database.\n\n[Full License Information](http://opendatacommons.org/licenses/by/summary)',
        'Public: Attribution, Share-Alike (ODbL)' => '**You are free:**\n\n* To Share: To copy, distribute and use the database.\n* To Create: To produce works from the database.\n* To Adapt: To modify, transform, and build upon the database.\n\n**As long as you:**\n\n* Attribute: You must attribute any public use of the database, or works produced from the database, in the manner specified in the license. For any use or redistribution of the database, or works produced from it, you must make clear to others the license of the database and keep intact any notices on the original database.\n* Share-Alike: If you publicly use any adapted version of this database, or works produced from an adapted database, you must also offer that adapted database under the ODbL.\n* Keep open: If you redistribute the database, or an adapted version of it, then you may use technological measures that restrict the work (such as DRM) as long as you also redistribute a version without such measures.\n\n[Full License Information](http://opendatacommons.org/licenses/odbl/summary)',
        'Private: All Rights Reserved' => 'This work is copyrighted by its author or licensor. You must not share, distribute, or modify this work without the prior consent of the author or licensor.',
        'Public: Attribution (CC BY 3.0)' => '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n* To make commercial use of the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n\n[Full License Information](http://creativecommons.org/licenses/by/3.0/)',
        'Public: Attribution, Share-Alike (CC BY-SA 3.0)' => '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n* To make commercial use of the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Share-Alike: If you alter, transform, or build upon this work, you may distribute the resulting work only under the same or similar license to this one.\n\n[Full License Information](http://creativecommons.org/licenses/by-sa/3.0/)',
        'Public: Attribution, No Derivative Works (CC BY-ND 3.0)' => '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To make commercial use of the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* No Derivative Works: You may not alter, transform, or build upon this work.\n\n[Full License Information](http://creativecommons.org/licenses/by-nd/3.0/)',
        'Public: Attribution, Non-Commercial (CC BY-NC 3.0)' => '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Non-Commercial: You may not use this work for commercial purposes.\n\n[Full License Information](http://creativecommons.org/licenses/by-nc/3.0/)',
        'Public: Attribution, Non-Commercial, Share-Alike (CC BY-NC-SA 3.0)' => '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Non-Commercial: You may not use this work for commercial purposes.\n* Share-Alike: If you alter, transform, or build upon this work, you may distribute the resulting work only under the same or similar license to this one.\n\n[Full License Information](http://creativecommons.org/licenses/by-nc-sa/3.0/)',
        'Public: Attribution, Non-Commercial, No Derivative Works (CC BY-NC-ND 3.0)' => '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Non-Commercial: You may not use this work for commercial purposes.\n* No Derivative Works: You may not alter, transform, or build upon this work.\n\n[Full License Information](http://creativecommons.org/licenses/by-nc-nd/3.0/)',
    );

    /** Post database upgrade. */
    public function postUpgrade()
    {
        foreach ($this->_licenses as $name => $text) {
            /** @var LicenseModel $licenseModel */
            $licenseModel = MidasLoader::loadModel('License');
            $licenseDaos = $licenseModel->getByName($name);

            /** @var LicenseDao $licenseDao */
            foreach ($licenseDaos as $licenseDao) {
                $licenseDao->setFulltext($text);
                $licenseModel->save($licenseDao);
            }
        }
    }
}
