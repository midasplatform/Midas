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
/** MetadataModelTest*/
class MetadataModelTest extends DatabaseTestCase
  {

  /** init test*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->_models = array('Metadata', 'ItemRevision');
    $this->_daos = array();
    parent::setUp();
    }


  /**
   * tests getMetadata and addMetadata
   */
  public function testGetMetadata()
    {
    // certain values are expected to be in the db by default
    $metadata = $this->Metadata->getMetadata(MIDAS_METADATA_TEXT, "contributor", "author");
    $this->assertEquals("Author of the data", $metadata->getDescription(), "author metadata had incorrect description");

    $metadata->setQualifier('artiste', 'more artistic than an artist');
    $this->Metadata->addMetadata(MIDAS_METADATA_TEXT, 'contributor', 'artiste', 'more artistic than an artist');
    $newMetadata = $this->Metadata->getMetadata(MIDAS_METADATA_TEXT, "contributor", "artiste");
    $this->assertEquals("Author of the data", $metadata->getDescription(), "more artistic than an artist");
    $this->Metadata->delete($newMetadata);
    }

  /**
   * tests getAllMetadata
   */
  public function testGetAllMetadata()
    {
    // expect at least a certain set of values in the db by default
    $metadataDaos = $this->Metadata->getAllMetadata();

    // look for 2 arrays of at least 14 each, and in specific author and created
    $author = array("element" => "contributor", "qualifier" => "author", "description" => "Author of the data");
    $created = array("element" => "date", "qualifier" => "created", "description" => "Date when the data was created");

    $rawMetadata = $metadataDaos['raw'];
    $sortedGlobalMetadata = $metadataDaos['sorted'][MIDAS_METADATA_TEXT];
    $this->assertGreaterThan(13, sizeof($rawMetadata), "expected at least 14 raw metadata");
    $this->assertGreaterThan(3, sizeof($sortedGlobalMetadata['identifier']), "expected at least 4 sorted identifier metadata");
    $this->assertGreaterThan(3, sizeof($sortedGlobalMetadata['description']), "expected at least 4 sorted description metadata");

    $authorFound = false;
    $createdFound = false;

    foreach($rawMetadata as $metadata)
      {
      if($metadata->getElement() === $author["element"] &&
         $metadata->getQualifier() === $author["qualifier"] &&
         $metadata->getDescription() === $author["description"])
        {
        $authorFound = true;
        }
      if($metadata->getElement() === $created["element"] &&
         $metadata->getQualifier() === $created["qualifier"] &&
         $metadata->getDescription() === $created["description"])
        {
        $createdFound = true;
        }
      }

    $this->assertTrue($authorFound, "Did not find author metadata");
    $this->assertTrue($createdFound, "Did not find created metadata");
    }

  /**
   * tests getTableValueName
   */
  public function testGetTableValueName()
    {
    // for now just test the GLOBAL
    $this->assertEquals($this->Metadata->getTableValueName(MIDAS_METADATA_TEXT), 'metadatavalue', 'GLOBAL table should be metadatavalue');
    }

  /**
   * tests getMetadataValueExists and addMetadataValue.
   */
  public function testGetMetadataValueExists()
    {
    // get a metadata
    $metadata = $this->Metadata->getMetadata(MIDAS_METADATA_TEXT, "contributor", "author");
    $metadata->setItemrevisionId(1);
    $metadata->setValue("DFW");
    $this->assertFalse($this->Metadata->getMetadataValueExists($metadata));

    $itemRevision = $this->ItemRevision->load(1);
    $this->Metadata->addMetadataValue($itemRevision, MIDAS_METADATA_TEXT, 'contributor', 'author', 'DFW');
    $this->assertTrue($this->Metadata->getMetadataValueExists($metadata));
    }

  }
