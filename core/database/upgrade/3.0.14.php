<?php

class Upgrade_3_0_14 extends MIDASUpgrade
{ 
  public function preUpgrade()
    {             
    // Insert common metadata
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','contributor','author','Author of the data')");
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','date','uploaded','Date when the data was uploaded')");
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','date','issued','Date when the data was released')");
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','date','created','Date when the data was created')");
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','identifier','citation','Citation of the data')");
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','identifier','uri','URI identifier')");
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','identifier','pubmed','PubMed identifier')");
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','identifier','doi','Digital Object Identifier')");
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','description','general','General description field')");
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','description','provenance','Provenance of the data')");
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','description','sponsorship','Sponsor of the data')");
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','description','publisher','Publisher of the data')");
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','subject','keyword','Keyword')");
    $this->db->query("INSERT INTO metadata (metadatatype,element,qualifier,description) VALUES ('0','subject','ocis','OCIS subject')");
    }
    
  public function mysql()
    {
    }

    
  public function pgsql()
    {
    }
    
  public function postUpgrade()
    {
    }
}
?>


