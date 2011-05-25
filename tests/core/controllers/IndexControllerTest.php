<?php
require_once dirname(__FILE__).'/../ControllerTestCase.php';
class IndexControllerTest extends ControllerTestCase
  {
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    parent::setUp();
    }

  public function testIndexAction()
    {
    $this->dispatchUrI("/index");
    $this->assertController("feed");
    $this->assertAction("index");   
    }

  }
