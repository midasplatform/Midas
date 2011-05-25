<?php
require_once BASE_PATH.'/core/models/dao/ItemRevisionDao.php';
require_once BASE_PATH.'/core/models/dao/BitstreamDao.php';
require_once BASE_PATH.'/core/models/dao/ItemDao.php';
require_once BASE_PATH.'/core/models/dao/MetadataDao.php';
require_once BASE_PATH.'/core/models/dao/AssetstoreDao.php';
require_once BASE_PATH.'/core/controllers/components/UploadComponent.php';

/** Migration tool*/
class MIDAS2MigrationComponent extends AppComponent
{ 
  /** These variables should be set by the UI */
  var $midas2User = "midas";
  var $midas2Password = "midas";
  var $midas2Host = "localhost";
  var $midas2Database = "midas";
  var $midas2Port = "5432";
  var $midas2Assetstore = "C:/xampp/midas/assetstore"; // without end slash
  var $assetstoreId = '1';
  
  /** Private variables */
  var $userId;
  
  /** function to create the items */
  private function _createFolderForItem($collectionId, $parentFolderid)
    {
    set_time_limit(0);
    $modelLoader = new MIDAS_ModelLoader;
    $Folder = $modelLoader->loadModel("Folder");  
    $Item = $modelLoader->loadModel("Item");  
    $ItemRevision = $modelLoader->loadModel("ItemRevision");
    $Group = $modelLoader->loadModel("Group");  
    $Assetstore = $modelLoader->loadModel("Assetstore");  
    $Folderpolicygroup = $modelLoader->loadModel("Folderpolicygroup");  
    $Folderpolicyuser = $modelLoader->loadModel("Folderpolicyuser");
    $Itempolicygroup = $modelLoader->loadModel("Itempolicygroup");
    
    $colquery = pg_query("SELECT i.item_id,mtitle.text_value AS title,mabstract.text_value AS abstract ".
                         "FROM item AS i ".
                         "LEFT JOIN metadatavalue AS mtitle ON (i.item_id = mtitle.item_id AND mtitle.metadata_field_id = 64) ".
                         "LEFT JOIN metadatavalue AS mabstract ON (i.item_id = mabstract.item_id AND mabstract.metadata_field_id = 27) ".
                         "WHERE i.owning_collection=".$collectionId);
    while($colquery_array = pg_fetch_array($colquery))
      {
      $item_id = $colquery_array['item_id'];
      $title = $colquery_array['title'];
     
      // If title is empty we skip this item
      if(empty($title))
        {
        continue; 
        }
      
      $abstract = $colquery_array['abstract'];
      $folderDao = false;
      try
        {
        // Create the folder for the item
        $folderDao = $Folder->createFolder($title, $abstract, $parentFolderid);
        
        // Assign the policies to the folder as the same as the parent folder
        $folder = $Folder->load($parentFolderid);
        $policyGroup = $folder->getFolderpolicygroup();
        $policyUser = $folder->getFolderpolicyuser();
        foreach($policyGroup as $policy)
          {
          $group = $policy->getGroup();
          $policyValue = $policy->getPolicy();
          $Folderpolicygroup->createPolicy($group, $folderDao, $policyValue);
          }
        foreach($policyUser as $policy)
          {
          $user = $policy->getUser();
          $policyValue = $policy->getPolicy();
          $Folderpolicyuser->createPolicy($user, $folderDao, $policyValue);
          }
        } 
      catch(Zend_Exception $e) 
        {
        $this->getLogger()->info($e->getMessage());
        Zend_Debug::dump($e);
        //we continue
        }
      
      if($folderDao)  
        { 
        // Create the item from the bitstreams
        $bitquery = pg_query("SELECT 	b.bitstream_id,b.name,b.description,b.internal_id FROM bitstream AS b,item2bitstream AS i2b ".
                             "WHERE i2b.bitstream_id = b.bitstream_id AND i2b.item_id=".$item_id);
        while($bitquery_array = pg_fetch_array($bitquery))
          {
          $filename = $bitquery_array['name'];
            
          $itemdao = new ItemDao;
          $itemdao->setName($filename);
          $Item->save($itemdao);
          
          // Set the policy of the item
          //$this->Itempolicyuser->createPolicy($this->userSession->Dao, $item, MIDAS_POLICY_ADMIN);    
          $anonymousGroup = $Group->load(MIDAS_GROUP_ANONYMOUS_KEY);
          $Itempolicygroup->createPolicy($anonymousGroup, $itemdao, MIDAS_POLICY_READ);
                 
          // Add the item to the current directory
          $Folder->addItem($folderDao, $itemdao);
          
          // Create a revision for the item
          $itemRevisionDao = new ItemRevisionDao;
          $itemRevisionDao->setChanges('Initial revision');    
          $itemRevisionDao->setUser_id($this->userId);
          $Item->addRevision($itemdao, $itemRevisionDao);

          // Add the metadata
          
          
          // Add bitstreams to the revision
          $bitstreamDao = new BitstreamDao;
          $bitstreamDao->setName($filename);
          
          // Compute the path from the internalid 
          // We are assuming only one assetstore
          $internal_id = $bitquery_array['internal_id'];
          $filepath = $this->midas2Assetstore.'/';
          $filepath .= substr($internal_id,0,2).'/';
          $filepath .= substr($internal_id,2,2).'/';
          $filepath .= substr($internal_id,4,2).'/';
          $filepath .= $internal_id;
          
          // Check that the file exists
          if(file_exists($filepath))
            { 
            // Upload the bitstream
            $assetstoreDao = $Assetstore->load($this->assetstoreId);
            $bitstreamDao->setPath($filepath);
            $bitstreamDao->fillPropertiesFromPath();
            $bitstreamDao->setAssetstoreId($this->assetstoreId);
            
            $UploadComponent = new UploadComponent();
            $UploadComponent->uploadBitstream($bitstreamDao, $assetstoreDao);
            unset($UploadComponent);
            
            // Upload the bitstream ifnecessary (based on the assetstore type)
            $ItemRevision->addBitstream($itemRevisionDao, $bitstreamDao);
            }
          }          
        }
      else
        {
        echo "Cannot create Folder for item: ".$title."<br>";
        }
      }
    } // end _createFolderForItem()
    
  /** function to create the collections */
  private function _createFolderForCollection($communityId, $parentFolderid)
    {
    set_time_limit(0);
    $modelLoader = new MIDAS_ModelLoader;
    $Folder = $modelLoader->loadModel("Folder");  
    $Folderpolicygroup = $modelLoader->loadModel("Folderpolicygroup");  
    $Folderpolicyuser = $modelLoader->loadModel("Folderpolicyuser");  
    
    $colquery = pg_query("SELECT collection_id,name,short_description,introductory_text FROM collection WHERE owning_community=".$communityId);
    while($colquery_array = pg_fetch_array($colquery))
      {
      $collection_id = $colquery_array['collection_id'];
      $name = $colquery_array['name'];
      $short_description = $colquery_array['short_description'];
      $introductory_text = $colquery_array['introductory_text'];
      $folderDao = false;
      try
        {
        // Create the folder for the community  
        $folderDao = $Folder->createFolder($name, $short_description, $parentFolderid);
        
        // Assign the policies to the folder as the same as the parent folder
        $folder = $Folder->load($parentFolderid);
        $policyGroup = $folder->getFolderpolicygroup();
        $policyUser = $folder->getFolderpolicyuser();
        foreach($policyGroup as $policy)
          {
          $group = $policy->getGroup();
          $policyValue = $policy->getPolicy();
          $Folderpolicygroup->createPolicy($group, $folderDao, $policyValue);
          }
        foreach($policyUser as $policy)
          {
          $user = $policy->getUser();
          $policyValue = $policy->getPolicy();
          $Folderpolicyuser->createPolicy($user, $folderDao, $policyValue);
          }
        } 
      catch(Zend_Exception $e) 
        {
        $this->getLogger()->info($e->getMessage());
        //Zend_Debug::dump($e);
        //we continue
        }
      
      if($folderDao)  
        { 
        // We should create the item
        $this->_createFolderForItem($collection_id, $folderDao->getFolderId());
        }
      else
        {
        echo "Cannot create Folder for collection: ".$name."<br>";
        }
      }
    } // end _createFolderForCollection()
  
    
  /** Recursive function to create the communities */
  private function _createFolderForCommunity($communityidMIDAS2, $parentFolderid)
    {
    set_time_limit(0);
    $modelLoader = new MIDAS_ModelLoader;
    $Folder = $modelLoader->loadModel("Folder");  
    $Folderpolicygroup = $modelLoader->loadModel("Folderpolicygroup");  
    $Folderpolicyuser = $modelLoader->loadModel("Folderpolicyuser");  
    
    // Create the collections attached to this community  
    $this->_createFolderForCollection($communityidMIDAS2,$parentFolderid);
        
    // Find the subcommunities
    $comquery = pg_query("SELECT community_id,name,short_description,introductory_text FROM community WHERE owning_community=".$communityidMIDAS2);
    while($comquery_array = pg_fetch_array($comquery))
      {
      $community_id = $comquery_array['community_id'];
      $name = $comquery_array['name'];
      $short_description = $comquery_array['short_description'];
      $introductory_text = $comquery_array['introductory_text'];
      $folderDao = false;
      try
        {
        // Create the folder for the community  
        $folderDao = $Folder->createFolder($name, $short_description, $parentFolderid);
        
        // Assign the policies to the folder as the same as the parent folder
        $folder = $Folder->load($parentFolderid);
        $policyGroup = $folder->getFolderpolicygroup();
        $policyUser = $folder->getFolderpolicyuser();
        foreach($policyGroup as $policy)
          {
          $group = $policy->getGroup();
          $policyValue = $policy->getPolicy();
          $Folderpolicygroup->createPolicy($group, $folderDao, $policyValue);
          }
        foreach($policyUser as $policy)
          {
          $user = $policy->getUser();
          $policyValue = $policy->getPolicy();
          $Folderpolicyuser->createPolicy($user, $folderDao, $policyValue);
          }
        } 
      catch(Zend_Exception $e) 
        {
        $this->getLogger()->info($e->getMessage());
        //Zend_Debug::dump($e);
        //we continue
        } 

       if($folderDao)  // The folder has been created for the community
        { 
        // Find the subcommunities
        $this->_createFolderForCommunity($community_id, $folderDao->getFolderId());
        }
       else
        {
        echo "Cannot create Folder for community: ".$name."<br>";
        } // end cannot create folder
      }  // end find information about the current community 
    } // end _createCommunity()
  
  /** */
  function migrate($userid)
    {       
    set_time_limit(0);
    $this->userId = $userid; 


    // Check that we are in development mode
    if(Zend_Registry::get('configGlobal')->environment != 'development')
      {
      throw new Zend_Exception("Please set your environment config variable to be 'development'.");  
      }
      
    // Connect to the local PGSQL database
    $pgdb = pg_connect("host='".$this->midas2Host."' port='".$this->midas2Port."' dbname='".$this->midas2Database.
                       "' user='".$this->midas2User."' password='".$this->midas2Password."'");
    if($pgdb === false)
      {
      throw new Zend_Exception("Cannot connect to the MIDAS2 database.");
      }
      
    // Check that the password prefix is not defined
    if(Zend_Registry::get('configGlobal')->password->prefix != '')
      {
      throw new Zend_Exception("Password prefix cannot be set because MIDAS2 doesn't use salt.");  
      }
      
    $modelLoader = new MIDAS_ModelLoader;
    
    // Just to test the metadata
    $MetadataModel = $modelLoader->loadModel("Metadata");  
    $Item = $modelLoader->loadModel("Item");  
    
    $itemDao = $Item->load(42);
    $itemRevisionDao = $Item->getLastRevision($itemDao);
    //$MetadataModel->addMetadata(MIDAS_METADATA_DOCUMENT,'contributor','author','Author of a text');
    $MetadataModel->addMetadataValue($itemRevisionDao,MIDAS_METADATA_DOCUMENT,
    																'contributor','author','Julien!');
    
    return false;
    
    // STEP 1: Import the users
    $User = $modelLoader->loadModel("User");  
    $query = pg_query("SELECT email,password,firstname,lastname FROM eperson");
    while($query_array = pg_fetch_array($query))
      {
      $email = $query_array['email'];
      $password = $query_array['password'];
      $firstname = $query_array['firstname'];
      $lastname = $query_array['lastname'];
      try
        {
        $userDao = $User->createUser($email, $password, $firstname, $lastname);
        $userDao->setPassword($password); // this is the encrypted password
        $User->save($userDao);
        } 
      catch(Zend_Exception $e) 
        {
        $this->getLogger()->info($e->getMessage());
        //Zend_Debug::dump($e);
        //we continue
        }
      }
    
    // STEP 2: Import the communities. The MIDAS2 TopLevel communities are communities in MIDAS3
    $Community = $modelLoader->loadModel("Community");
    $query = pg_query("SELECT community_id,name,short_description,introductory_text FROM community WHERE owning_community = 0");
    while($query_array = pg_fetch_array($query))
      {
      $community_id = $query_array['community_id'];
      $name = $query_array['name'];
      $short_description = $query_array['short_description'];
      $introductory_text = $query_array['introductory_text'];
      $communityDao = false;
      try
        {
        $communityDao = $Community->createCommunity($name, $short_description, MIDAS_COMMUNITY_PUBLIC, NULL); // no user 
        } 
      catch(Zend_Exception $e) 
        {
        $this->getLogger()->info($e->getMessage());
        //Zend_Debug::dump($e);
        //we continue
        }
      
      if(!$communityDao)  
        {
        $communityDao = $Community->getByName($name);
        }
      
      if($communityDao)  
        {
        $folderId = $communityDao->getFolderId();  
        $this->_createFolderForCommunity($community_id, $folderId);
        }
      else
        {
        echo "Cannot create community: ".$name."<br>";
        }       
      } // end while loop        
      
    // Close the database connection  
    pg_close($pgdb);
    
    echo "Migration done. Enjoy MIDAS3!";
    } // end function migrate()  
    
} // end class
