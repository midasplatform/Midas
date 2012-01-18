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
/** test search controller*/
class SearchControllerTest extends ControllerTestCase
  {

  /** init test*/
  public function setUp()
    {
    $this->setupDatabase(array('search'));
    $this->_models = array('User');

    parent::setUp();
    }

  /** Test the search results page */
  public function testIndexAction()
    {
    $this->resetAll();
    $this->dispatchUrI('/search/?q=name');
    $this->assertController('search');
    $this->assertAction('index');

    $this->resetAll();
    $this->dispatchUrI('/search/?q=name&ajax');
    $this->assertController('search');
    $this->assertAction('index');

    $resp = json_decode($this->getBody());
    // we don't make assertions about items since we aren't fully in control of the
    // state of the lucene cache at test time, and we don't want to wipe it.
    $this->assertEquals($resp->nfolders, 2);
    $this->assertEquals($resp->ncommunities, 0);
    $this->assertEquals($resp->nusers, 2);

    $this->assertTrue(count($resp->results) >= 4);

    foreach($resp->results as $result)
      {
      $this->assertTrue($result->resultType == 'user' || $result->resultType == 'folder' || $result->resultType == 'item');

      switch($result->resultType)
        {
        case 'user':
          $this->assertTrue(is_numeric($result->user_id));
          $this->assertNotEmpty($result->firstname);
          $this->assertNotEmpty($result->lastname);
          break;
        case 'folder':
          $this->assertTrue(is_numeric($result->folder_id));
          $this->assertNotEmpty($result->name);
          break;
        case 'item':
          break;
        default:
          break;
        }
      }
    }

  /** Test the live search response */
  public function testLiveSearch()
    {
    $this->resetAll();
    $this->dispatchUrI('/search/live?term=name');
    $this->assertController('search');
    $this->assertAction('live');

    $resp = json_decode($this->getBody());

    // Ensure we get folder and user results from live search
    $this->assertTrue(count($resp) >= 4);
    foreach($resp as $result)
      {
      $this->assertTrue($result->category == 'Users' || $result->category == 'Folders' || $result->category == 'Items');

      switch($result->category)
        {
        case 'Users':
          $this->assertTrue(is_numeric($result->userid));
          $this->assertNotEmpty($result->value);
          break;
        case 'Folders':
          $this->assertTrue(is_numeric($result->folderid));
          $this->assertNotEmpty($result->value);
          break;
        case 'Items':
          break;
        default:
          break;
        }
      }

    // Ensure we get community results from live search
    $this->resetAll();
    $this->dispatchUrI('/search/live?term=Community');
    $this->assertController('search');
    $this->assertAction('live');
    $resp = json_decode($this->getBody());
    $this->assertEquals(count($resp), 1);
    $this->assertEquals($resp[0]->category, 'Communities');
    $this->assertTrue(is_numeric($resp[0]->communityid));
    $this->assertNotEmpty($resp[0]->label);
    $this->assertNotEmpty($resp[0]->value);

    // Ensure we get group results from live search with shareSearch enabled
    $this->resetAll();
    $this->dispatchUrI('/search/live?term=Community&shareSearch');
    $this->assertController('search');
    $this->assertAction('live');
    $resp = json_decode($this->getBody());
    $this->assertEquals(count($resp), 3);
    foreach($resp as $result)
      {
      $this->assertEquals($result->category, 'Groups');
      $this->assertTrue(is_numeric($result->groupid));
      $this->assertNotEmpty($result->label);
      $this->assertNotEmpty($result->value);
      }

    // Ensure we get only user results from live search with userSearch enabled
    $this->resetAll();
    $this->dispatchUrI('/search/live?term=name&userSearch');
    $this->assertController('search');
    $this->assertAction('live');
    $resp = json_decode($this->getBody());
    $this->assertEquals(count($resp), 2);
    foreach($resp as $result)
      {
      $this->assertEquals($result->category, 'Users');
      $this->assertTrue(is_numeric($result->userid));
      $this->assertNotEmpty($result->label);
      $this->assertNotEmpty($result->value);
      }

    // No good assertions about items for now, due to lucene database being in unknown state
    $this->resetAll();
    $this->dispatchUrI('/search/live?term=name&itemSearch');
    $this->assertController('search');
    $this->assertAction('live');

    $usersFile = $this->loadData('User', 'search');
    $userDao = $this->User->load($usersFile[2]->getKey());
    $this->resetAll();
    $this->dispatchUrI('/search/live?term=invalid&itemSearch', $userDao);
    $this->assertController('search');
    $this->assertAction('live');
    }
  }
