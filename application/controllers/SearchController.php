<?php

/**
 *  Search controller
 */
class SearchController extends AppController
{
  public $_models=array('ItemKeyword','Item','Folder','User','Community');
  public $_daos=array('ItemKeyword','Item','Folder','USer','Community');
  public $_components=array();
    
  /** Init Controller */
  function init()
    { 
    $this->view->activemenu = 'feed'; // set the active menu
    }  // end init()  

 /** search live Action */
  public function indexAction()
    {
    $this->view->header=$this->t("Search");  
    
    // Pass the keyword to javascript  
    $keyword = $this->getRequest()->getParam('q');
    $this->view->json['search']['keyword'] = $keyword;
    
    // Get the items corresponding to the search
    $items = $this->ItemKeyword->getItemsFromSearch($keyword,$this->userSession->Dao);
    $this->view->items=$items;
    }
    
  /** search live Action */
  public function liveAction()
    {
    // This is necessary in order to avoid session lock and being able to run two 
    // ajax requests simultaneously
    session_write_close();
      
    $search = $this->getRequest()->getParam('term');
    
    // Search for the items
    $ItemsDao = $this->ItemKeyword->getItemsFromSearch($search,$this->userSession->Dao);
    
    // Search for the folders
    $FoldersDao = $this->Folder->getFoldersFromSearch($search,$this->userSession->Dao); 
    
    // Search for the communities
    $CommunitiesDao = $this->Community->getCommunitiesFromSearch($search,$this->userSession->Dao); 
    
    // Search for the users
    $UsersDao = $this->User->getUsersFromSearch($search,$this->userSession->Dao); 
    
    // Compute how many of each we should display
    $nitems = count($ItemsDao);
    $nfolders = count($FoldersDao);
    $ncommunities = count($CommunitiesDao);
    $nusers = count($UsersDao);
    
    $nmaxfolders = ($nfolders < 3) ? $nfolders : 3;
    $nmaxcommunities = ($ncommunities < 3) ? $ncommunities : 3;
    $nmaxusers = ($nusers < 3) ? $nusers : 3;
    
    if($nitems>5)
      {
      $nitems = 14-($nmaxfolders+$nmaxcommunities+$nmaxusers);
      }
    
    if($nfolders>3)
      {
      $nfolders = 14-($nitems+$nmaxcommunities+$nmaxusers);
      }

    if($ncommunities>3)
      {
      $ncommunities = 14-($nitems+$nfolders+$nmaxusers);
      }

    if($nusers>3)
      {
      $nusers = 14-($nitems+$nfolders+$ncommunities);
      }  
      
    // Return the JSON results
    echo '[';
    $id=1;
    $n=0;
    // Items
    foreach($ItemsDao as $itemDao)
      {
      if($n == $nitems)
        {
        break;  
        }  
      if($id>1)
        {
        echo ',';    
        }
      echo '{'; 
      echo '"id":"'.$id.'"'; 
      echo ', "label":"'.$itemDao->getName();
      if($itemDao->count>1)
        {
        echo ' ('.$itemDao->count.')"';
        }
      else 
        {
        echo '"';  
        } 
      
      echo ', "value":"'.$itemDao->getName().'"'; 
      
      if($itemDao->count==1)
        {
        echo ', "itemid":"'.$itemDao->getItemId().'"'; 
        }
      echo ', "category":"Items"';   
      $id++;
      $n++;
      echo '}'; 
      }

    // Folder  
    $n=0;
    foreach($FoldersDao as $folderDao)
      {
      if($n == $nfolders)
        {
        break;  
        }
      if($id>1)
        {
        echo ',';    
        }
      echo '{'; 
      echo '"id":"'.$id.'"'; 
      echo ', "label":"'.$folderDao->getName();
      if($folderDao->count>1)
        {
        echo ' ('.$folderDao->count.')"';
        }
      else 
        {
        echo '"';  
        } 
      echo ', "value":"'.$folderDao->getName().'"';
      if($folderDao->count==1)
        {
        echo ', "folderid":"'.$folderDao->getFolderId().'"'; 
        } 
      echo ', "category":"Folders"';   
      $id++;
      $n++;
      echo '}'; 
      } 
      
    // Community  
    $n=0;
    foreach($CommunitiesDao as $communityDao)
      {
      if($n == $ncommunities)
        {
        break;  
        }  
      if($id>1)
        {
        echo ',';    
        }
      echo '{'; 
      echo '"id":"'.$id.'"'; 
      echo ', "label":"'.$communityDao->getName();
      if($communityDao->count>1)
        {
        echo ' ('.$communityDao->count.')"';
        }
      else 
        {
        echo '"';  
        } 
      echo ', "value":"'.$communityDao->getName().'"'; 
      if($communityDao->count==1)
        {
        echo ', "communityid":"'.$communityDao->getKey().'"'; 
        }
      echo ', "category":"Communities"';   
      $id++;
      $n++;
      echo '}'; 
      }
       
    // User
    $n=0;
    foreach($UsersDao as $userDao)
      {
      if($n == $nusers)
        {
        break;  
        }
      if($id>1)
        {
        echo ',';    
        }
      echo '{'; 
      echo '"id":"'.$id.'"'; 
      echo ', "label":"'.$userDao->getFirstname().' '.$userDao->getLastname();
      if($userDao->count>1)
        {
        echo ' ('.$userDao->count.')"';
        }
      else 
        {
        echo '"';  
        } 
      echo ', "value":"'.$userDao->getFirstname().' '.$userDao->getLastname().'"';
      if($userDao->count==1)
        {
        echo ', "userid":"'.$userDao->getUserId().'"'; 
        }
      echo ', "category":"Users"';   
      $id++;
      $n++;
      echo '}';
      }   
      
    echo ']';
    exit();  
    }
    
} // end class

  