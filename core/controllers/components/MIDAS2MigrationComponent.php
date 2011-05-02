<?php
require_once BASE_PATH.'/core/models/dao/ItemRevisionDao.php';
require_once BASE_PATH.'/core/models/dao/BitstreamDao.php';
require_once BASE_PATH.'/core/models/dao/ItemDao.php';

class MIDAS2MigrationComponent extends AppComponent
{ 
  
  /**  function to create the items */
  function _createFolderForItem($collectionId,$parentFolderid)
    {
    set_time_limit(0);
    $modelLoader = new MIDAS_ModelLoader;
    $Folder = $modelLoader->loadModel("Folder");  
    $Item = $modelLoader->loadModel("Item");  
    $ItemRevision = $modelLoader->loadModel("ItemRevision");
    $Group = $modelLoader->loadModel("Group");  
    $Folderpolicygroup = $modelLoader->loadModel("Folderpolicygroup");  
    $Folderpolicyuser = $modelLoader->loadModel("Folderpolicyuser");
    $Itempolicygroup = $modelLoader->loadModel("Itempolicygroup");
    
    $colquery=pg_query("SELECT i.item_id,mtitle.text_value AS title,mabstract.text_value AS abstract
                         FROM item AS i 
                         LEFT JOIN metadatavalue AS mtitle ON (i.item_id=mtitle.item_id AND mtitle.metadata_field_id=64)
                         LEFT JOIN metadatavalue AS mabstract ON (i.item_id=mabstract.item_id AND mabstract.metadata_field_id=27)
    										 WHERE i.owning_collection=".$collectionId);
    while($colquery_array = pg_fetch_array($colquery))
      {
      $item_id = $colquery_array['item_id'];
      $title = $colquery_array['title'];
      $abstract = $colquery_array['abstract'];
      $folderDao = false;
      try
        {
        // Create the folder for the community  
        $folderDao = $Folder->createFolder($title,$abstract,$parentFolderid);
        
        // Assign the policies to the folder as the same as the parent folder
        $folder= $Folder->load($parentFolderid);
        $policyGroup=$folder->getFolderpolicygroup();
        $policyUser=$folder->getFolderpolicyuser();
        foreach($policyGroup as $policy)
          {
          $group=$policy->getGroup();
          $policyValue=$policy->getPolicy();
          $Folderpolicygroup->createPolicy($group,$folderDao,$policyValue);
          }
        foreach($policyUser as $policy)
          {
          $user=$policy->getUser();
          $policyValue=$policy->getPolicy();
          $Folderpolicyuser->createPolicy($user,$folderDao,$policyValue);
          }
        } 
      catch(Zend_Exception $e) 
        {
        //Zend_Debug::dump($e);
        //we continue
        }
      
      if($folderDao)  
        { 
        // Create the item from the bitstreams
        $bitquery=pg_query("SELECT 	b.bitstream_id,b.name,b.description,b.internal_id FROM bitstream AS b,item2bitstream AS i2b
        										WHERE i2b.bitstream_id=b.bitstream_id AND i2b.item_id=".$item_id);
        while($bitquery_array = pg_fetch_array($bitquery))
          {
          $filename = $bitquery_array['name'];
            
          $itemdao = new ItemDao;
          $itemdao->setName($filename);
          $Item->save($itemdao);
          
          // Set the policy of the item
          //$this->Itempolicyuser->createPolicy($this->userSession->Dao,$item,MIDAS_POLICY_ADMIN);    
          $anonymousGroup=$Group->load(MIDAS_GROUP_ANONYMOUS_KEY);
          $Itempolicygroup->createPolicy($anonymousGroup,$itemdao,MIDAS_POLICY_READ);
                 
          // Add the item to the current directory
          $Folder->addItem($folderDao,$itemdao);
          
          // Create a revision for the item
          $itemRevisionDao = new ItemRevisionDao;
          $itemRevisionDao->setChanges('Initial revision');    
          //$itemRevisionDao->setUser_id($this->userSession->Dao->getUserId());
          $Item->addRevision($itemdao,$itemRevisionDao);

          // Add bitstreams to the revision
          $bitstreamDao = new BitstreamDao;
          $bitstreamDao->setName($filename);
          
          // Compute the path from the internalid 
          $internal_id = $bitquery_array['internal_id'];
          $bitstreamDao->setPath($internal_id);
          //$bitstreamDao->fillPropertiesFromPath();
          //$bitstreamDao->setAssetstoreId($this->assetstoreid);
          
          // Upload the bitstream if necessary (based on the assetstore type)
          $ItemRevision->addBitstream($itemRevisionDao,$bitstreamDao);
          
          // We should add the metadata as well  
          
          }
          
        }
      else
        {
        echo "Cannot create Folder for item: ".$title."<br>";
        }
      }
    } // end _createFolderForItem()
    
  /**  function to create the collections */
  function _createFolderForCollection($communityId,$parentFolderid)
    {
    set_time_limit(0);
    $modelLoader = new MIDAS_ModelLoader;
    $Folder = $modelLoader->loadModel("Folder");  
    $Folderpolicygroup = $modelLoader->loadModel("Folderpolicygroup");  
    $Folderpolicyuser = $modelLoader->loadModel("Folderpolicyuser");  
    
    $colquery=pg_query("SELECT collection_id,name,short_description,introductory_text FROM collection WHERE owning_community=".$communityId);
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
        $folderDao = $Folder->createFolder($name,$short_description,$parentFolderid);
        
        // Assign the policies to the folder as the same as the parent folder
        $folder= $Folder->load($parentFolderid);
        $policyGroup=$folder->getFolderpolicygroup();
        $policyUser=$folder->getFolderpolicyuser();
        foreach($policyGroup as $policy)
          {
          $group=$policy->getGroup();
          $policyValue=$policy->getPolicy();
          $Folderpolicygroup->createPolicy($group,$folderDao,$policyValue);
          }
        foreach($policyUser as $policy)
          {
          $user=$policy->getUser();
          $policyValue=$policy->getPolicy();
          $Folderpolicyuser->createPolicy($user,$folderDao,$policyValue);
          }
        } 
      catch(Zend_Exception $e) 
        {
        //Zend_Debug::dump($e);
        //we continue
        }
      
      if($folderDao)  
        { 
        // We should create the item
        $this->_createFolderForItem($collection_id,$folderDao->getFolderId());
        }
      else
        {
        echo "Cannot create Folder for collection: ".$name."<br>";
        }
      }
    } // end _createFolderForCollection()
  
    
  /** Recursive function to create the communities */
  function _createFolderForCommunity($parentidMIDAS2,$parentFolderid)
    {
    set_time_limit(0);
    $modelLoader = new MIDAS_ModelLoader;
    $Folder = $modelLoader->loadModel("Folder");  
    $Folderpolicygroup = $modelLoader->loadModel("Folderpolicygroup");  
    $Folderpolicyuser = $modelLoader->loadModel("Folderpolicyuser");  
    
    $comquery=pg_query("SELECT community_id,name,short_description,introductory_text FROM community WHERE owning_community=".$parentidMIDAS2);
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
        $folderDao = $Folder->createFolder($name,$short_description,$parentFolderid);
        
        // Assign the policies to the folder as the same as the parent folder
        $folder= $Folder->load($parentFolderid);
        $policyGroup=$folder->getFolderpolicygroup();
        $policyUser=$folder->getFolderpolicyuser();
        foreach($policyGroup as $policy)
          {
          $group=$policy->getGroup();
          $policyValue=$policy->getPolicy();
          $Folderpolicygroup->createPolicy($group,$folderDao,$policyValue);
          }
        foreach($policyUser as $policy)
          {
          $user=$policy->getUser();
          $policyValue=$policy->getPolicy();
          $Folderpolicyuser->createPolicy($user,$folderDao,$policyValue);
          }
        } 
      catch(Zend_Exception $e) 
        {
        //Zend_Debug::dump($e);
        //we continue
        }
      
      if($folderDao)  
        { 
        $this->_createFolderForCommunity($community_id,$folderDao->getFolderId());
        }
      else
        {
        echo "Cannot create Folder for community: ".$name."<br>";
        }
      }

    // Create the collections attached to this community  
    $this->_createFolderForCollection($parentidMIDAS2,$parentFolderid);
          
    } // end _createCommunity()
  
  /** */
  function migrate()
    {
    set_time_limit(0);
      
    // Check that we are in development mode
    if(Zend_Registry::get('configGlobal')->environment != 'development')
      {
      throw new Zend_Exception("Please set your environment config variable to be 'development'.");  
      }
      
    // Connect to the local PGSQL database
    $pgdb = pg_connect("host=localhost port=5432 dbname=midasopen user=midas password=midas");
    if($pgdb === false)
      {
      throw new Zend_Exception("Cannot connect to the MIDAS2 database.");
      }
      
    // Check that the password prefix is not defined
    if(Zend_Registry::get('configGlobal')->password->prefix != '')
      {
      throw new Zend_Exception("Cannot connect to the MIDAS2 database.");  
      }
      
    $modelLoader = new MIDAS_ModelLoader;
    
    // STEP 1: Import the users
    $User = $modelLoader->loadModel("User");  
    $query=pg_query("SELECT email,password,firstname,lastname FROM eperson");
    while($query_array = pg_fetch_array($query))
      {
      $email = $query_array['email'];
      $password = $query_array['password'];
      $firstname = $query_array['firstname'];
      $lastname = $query_array['lastname'];
      try
        {
        $userDao = $User->createUser($email,$password,$firstname,$lastname);
        $userDao->setPassword($password); // this is the encrypted password
        $User->save($userDao);
        } 
      catch(Zend_Exception $e) 
        {
        //Zend_Debug::dump($e);
        //we continue
        }
      }
    
    // STEP 2: Import the communities. The MIDAS2 TopLevel communities are communities in MIDAS3
    $Community = $modelLoader->loadModel("Community");
    $query=pg_query("SELECT community_id,name,short_description,introductory_text FROM community WHERE owning_community=0");
    while($query_array = pg_fetch_array($query))
      {
      $community_id = $query_array['community_id'];
      $name = $query_array['name'];
      $short_description = $query_array['short_description'];
      $introductory_text = $query_array['introductory_text'];
      $communityDao = false;
      try
        {
        $communityDao = $Community->createCommunity($name,$short_description,MIDAS_COMMUNITY_PUBLIC,NULL); // no user 
        } 
      catch(Zend_Exception $e) 
        {
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
        $this->_createFolderForCommunity($community_id,$folderId);
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
?>