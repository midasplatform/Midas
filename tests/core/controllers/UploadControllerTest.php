<?php
require_once dirname(__FILE__).'/../ControllerTestCase.php';
class UploadControllerTest extends ControllerTestCase
  {
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models=array('User', 'Feed', 'Assetstore', 'ItemKeyword', 'Item');
    $this->_daos=array('User', 'Assetstore');
    parent::setUp();
    }

  /** testGethttpuploadoffsetAction*/
  function testGethttpuploadoffsetAction()
    {
    $identifier = BASE_PATH.'/tmp/misc/httpupload.png';
    @unlink($identifier);
    copy(BASE_PATH.'/tests/testfiles/search.png', $identifier);
    $page = "upload/gethttpuploadoffset/?uploadUniqueIdentifier=httpupload.png&testingmode=1";
    $this->dispatchUrI($page);
    
    $content = $this->getBody();
    if(strpos($content, "[OK]") === false)
      {
      $this->fail();
      }
    if(strpos($content, "[ERROR]") !== false)
      {
      $this->fail();
      }
    }

  /** gethttpuploaduniqueidentifierAction*/
  function testGethttpuploaduniqueidentifierAction()
    {
    $identifier = BASE_PATH.'/tmp/misc/httpupload.png';
    @unlink($identifier);
    copy(BASE_PATH.'/tests/testfiles/search.png', $identifier);
    $page = "upload/gethttpuploaduniqueidentifier/?filename=httpupload.png&testingmode=1";
    $this->dispatchUrI($page);
    $content = $this->getBody();
    if(strpos($content, "[OK]") === false)
      {
      $this->fail();
      }
    if(strpos($content, "[ERROR]") !== false)
      {
      $this->fail();
      }
    }
    
  /** processjavaupload*/
  function testProcessjavauploadAction()
    {
    $this->setupDatabase(array('default'));
    $fileBase = BASE_PATH.'/tests/testfiles/search.png';
    $file = BASE_PATH.'/tmp/misc/testing_file.png';
    $identifier = BASE_PATH.'/tmp/misc/httpupload.png';
    
    @unlink($identifier);
    copy($fileBase, $file);
    $ident = fopen($identifier, "x+");
    fwrite($ident, " ");
    fclose($ident);
    chmod ($identifier, 0777);
    
    $params = "testingmode=1&filename=search.png&path=".$file."&length=".filesize($file)."&uploadUniqueIdentifier=".basename($identifier);
    $page = $this->webroot."item/process_http_upload/".$this->item."?".$params;
    
    $page = "upload/processjavaupload/?".$params;
    
    $usersFile=$this->loadData('User','default');
    $userDao=$this->User->load($usersFile[0]->getKey());
    $this->dispatchUrI($page, $userDao);

    $search = $this->ItemKeyword->getItemsFromSearch('search.png', $userDao);
    if(empty($search))
      {
      $this->fail('Unable to find item');
      }
    $this->setupDatabase(array('default'));
    }
    
  /** simpleuploadAction*/
  function testSimpleuploadAction()
    {
    $this->dispatchUrI("/upload/simpleupload", null, true); 
    
    $usersFile=$this->loadData('User','default');
    $userDao=$this->User->load($usersFile[0]->getKey());
    
    $folder = $userDao->getPublicFolder();
    $this->dispatchUrI("/upload/simpleupload?parent=".$folder->getKey(), $userDao, false); 
    $this->assertContains('id="destinationId" value="'.$folder->getKey(), $this->getBody()); 
    
    $this->resetAll();
    $folder = $userDao->getPrivateFolder();
    $this->dispatchUrI("/upload/simpleupload", $userDao, false); 
    $this->assertContains('id="destinationId" value="'.$folder->getKey(), $this->getBody());
    }
    
  /** revision*/
  function testRevision()
    {
    $this->dispatchUrI("/upload/revision", null, true); 
    
    $usersFile=$this->loadData('User','default');
    $userDao=$this->User->load($usersFile[0]->getKey());
    
    $itemsFile = $this->loadData('Item','default');
    $itemDao=$this->Item->load($itemsFile[1]->getKey());

    $this->dispatchUrI("/upload/revision?itemId=".$itemDao->getKey(), $userDao); 
    }
    
  /** savelink*/
  function testSavelinkAction()
    {
    $this->dispatchUrI("/upload/savelink", null, true); 
    
    $usersFile=$this->loadData('User','default');
    $userDao=$this->User->load($usersFile[0]->getKey());
    
    $itemsFile = $this->loadData('Item','default');
    $itemDao=$this->Item->load($itemsFile[1]->getKey());

    $this->params = array();
    $this->params['parent'] = $userDao->getPublicFolder()->getKey();
    $this->params['name'] = 'test name link';
    $this->params['url'] = 'http://www.kitware.com';
    $this->params['license'] = 0;
    $this->dispatchUrI("/upload/savelink", $userDao); 
    
    $search = $this->ItemKeyword->getItemsFromSearch($this->params['name'], $userDao);
    if(empty($search))
      {
      $this->fail('Unable to find item');
      }
    $this->setupDatabase(array('default'));
    }
    
  /** saveuploadedAction*/
  function testDaveuploadedAction()
    {    
    $this->setupDatabase(array('default'));
    
    $usersFile=$this->loadData('User','default');
    $userDao=$this->User->load($usersFile[0]->getKey());

    $this->params = array();
    $this->params['parent'] = $userDao->getPublicFolder()->getKey();
    $this->params['license'] = 0;
    $this->dispatchUrI("/upload/saveuploaded", $userDao); 
    
    $search = $this->ItemKeyword->getItemsFromSearch('search.png', $userDao);
    if(empty($search))
      {
      $this->fail('Unable to find item');
      }
    $this->setupDatabase(array('default'));
    }
    
  }
