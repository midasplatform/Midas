<?php

require_once dirname(__FILE__) . '/../../DatabaseTestCase.php';
class ItemModelTest extends DatabaseTestCase
  {
  public function setUp()
    {
    $this->setupDatabase(array());
    $this->_models=array(
      'Item','ItemRevision','ItemKeyword'
    );
    $this->_daos=array('ItemKeyword');
    parent::setUp();
    }

  public function testGetLastRevision()
    {
    $itemsFile=$this->loadData('Item','default');
    $revisionsFile=$this->loadData('ItemRevision','default');
    $revision=$this->Item->getLastRevision($itemsFile[0]);
    $this->assertEquals($revisionsFile[1]->getKey(),$revision->getKey());
    }

  public function testAddRevision()
    {
    $itemsFile=$this->loadData('Item','default');
    $usersFile=$this->loadData('User','default');
    $revision=new ItemRevisionDao();
    $revision->setUserId($usersFile[0]->getKey());
    $revision->setDate(date('c'));
    $revision->setChanges("change");
    $this->ItemRevision->save($revision);

    $this->Item->addRevision($itemsFile[1],$revision);
    $revisionTmp=$this->Item->getLastRevision($itemsFile[1]);
    $this->assertEquals($revision->getKey(),$revisionTmp->getKey());
    }

    /*
  public function testAddKeyword()
    {
    $itemsFile=$this->loadData('Item','default');
    $usersFile=$this->loadData('User','default');
    $keyword=new ItemKeywordDao();
    $keyword->setValue('testKeyword');
    $keyword->setRelevance(1);
    $this->ItemKeyword->save($keyword);
    $this->Item->addKeyword($itemsFile[1],$keyword);
    $keywordTmp=$this->Item->getLastRevision($itemsFile[1]);
    $this->assertEquals($keyword->getKey(),$keywordTmp->getKey());
    }*/
  }
