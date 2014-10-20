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

/** Test download controller */
class Javauploaddownload_DownloadControllerTest extends ControllerTestCase
{
    /** Initialize tests */
    public function setUp()
    {
        $this->enabledModules = array('javauploaddownload');
        $this->_daos = array('Item');
        $this->_models = array('Item', 'User');
        parent::setUp();
    }

    /** Test Java download applet prompt is triggered for large downloads */
    public function testPromptApplet()
    {
        $this->resetAll();
        $this->setupDatabase(array('default'));
        $this->dispatchUri('/download/checksize?itemIds=1000', null);
        $json = json_decode($this->getBody(), true);
        $this->assertTrue(isset($json['action']));
        $this->assertEquals($json['action'], 'download');
        $this->resetAll();
        $item = $this->Item->load(1000);
        $item->setSizebytes(2415919104); // 2.25 GB
        $this->Item->save($item);
        $this->dispatchUri('/download/checksize?itemIds=1000', null);
        $json = json_decode($this->getBody(), true);
        $this->assertTrue(isset($json['action']));
        $this->assertEquals($json['action'], 'promptApplet');
        $this->assertEquals($json['sizeStr'], '2.3 GB');
    }

    /** Test rendering of the Java download applet view */
    public function testAppletViewAction()
    {
        $this->resetAll();
        $this->setupDatabase(array('default'));
        $adminUser = $this->User->load(3);
        $this->dispatchUri('/javauploaddownload/download?folderIds=1002', null, true);
        $this->resetAll();
        $this->dispatchUri('/javauploaddownload/download?folderIds=1002', $adminUser);
        $this->assertQuery('param[name="itemIds"]');
        $this->assertQuery('param[name="folderIds"][value="1002"]');
        $this->assertQuery('param[name="totalSize"][value="0"]');
        $this->resetAll();
        $item = $this->Item->load(1000);
        $item->setSizebytes(2415919104);
        $this->Item->save($item);
        $this->dispatchUri('/javauploaddownload/download?itemIds=1000', $adminUser);
        $this->assertQuery('param[name="itemIds"][value="1000"]');
        $this->assertQuery('param[name="folderIds"]');
        $this->assertQuery('param[name="totalSize"][value="2415919104"]');
    }
}
