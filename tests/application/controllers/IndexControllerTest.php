<?php
require_once dirname(__FILE__).'/../ControllerTestCase.php';
class IndexControllerTest extends ControllerTestCase
  {
  public function setUp()
    {
    $this->setupDatabase(array());
    parent::setUp();
    }

  public function testAboutAction()
    {
    $this->dispatchUrI("/browse");
    $this->assertQueryContentContains('div.viewHeader', 'Data');
    }

  }
