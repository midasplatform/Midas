<?php

/**
 *  Search controller
 */
class SearchController extends AppController
{
  public $_models=array('ItemKeyword','Item','Folder','User','Community','Group');
  public $_daos=array('ItemKeyword','Item','Folder','User','Community');
  public $_components=array('Sortdao','Date');
    
  /** Init Controller */
  function init()
    { 
    $this->view->activemenu = 'feed'; // set the active menu
    
    // if the number of parameters is more than 3 then it's the liveAction or advanced search
    if(count($this->_getAllParams()) == 3)
      {
      $actionName=Zend_Controller_Front::getInstance()->getRequest()->getActionName();
      $this->_forward('index',null,null,array('q'=>$actionName));
      }
    }  // end init()


 /** search live Action */
  public function indexAction()
    {
    $this->view->header=$this->t("Search");  
    
    // Pass the keyword to javascript  
    $keyword = $this->getRequest()->getParam('q');
    $this->view->json['search']['keyword'] = $keyword;
    
    $ajax=$this->_getParam('ajax');
    $order=$this->_getParam('order');
    if(!isset($order))
      {
      $order='view';
      }
    // Get the items corresponding to the search
    $ItemsDao = $this->ItemKeyword->getItemsFromSearch($keyword,$this->userSession->Dao,200,false,$order);
    
    // Search for the folders
    $FoldersDao = $this->Folder->getFoldersFromSearch($keyword,$this->userSession->Dao,15,false,$order); 
     
    // Search for the communities
    $CommunitiesDao = $this->Community->getCommunitiesFromSearch($keyword,$this->userSession->Dao,15,false,$order); 
    
    // Search for the users
    $UsersDao = $this->User->getUsersFromSearch($keyword,$this->userSession->Dao,15,false,$order); 
    
    $results=$this->formatResults($order, $ItemsDao, $FoldersDao, $CommunitiesDao, $UsersDao);
    
    if(isset($ajax))
      {
      $this->_helper->layout->disableLayout();
      $this->_helper->viewRenderer->setNoRender();
      echo JsonComponent::encode($results);
      }
    else
      {
      $this->view->nitems=count($ItemsDao);
      $this->view->nfolders=count($FoldersDao);
      $this->view->ncommunities=count($CommunitiesDao);
      $this->view->nusers=count($UsersDao);
      $this->view->json['search']['results']=$results;
      $this->view->json['search']['keyword'] = $keyword;
      $this->view->json['search']['noResults'] = $this->t('No result found.');
      $this->view->json['search']['moreResults'] = $this->t('Show more results.');
      }
    }//end indexAction
    
  /** 
   * Format search results
   * @param string $order
   * @param Array $items
   * @param Array $folders
   * @param Array $communities
   * @param Array $users
   * @return Array 
   */
  private function formatResults($order,$items,$folders,$communities,$users)
    {
    foreach ($users as $key=>$user)
      {
      $users[$key]->name=$user->getLastname();
      $users[$key]->date=$user->getCreation();
      }
    foreach ($communities as $key=>$community)
      {
      $communities[$key]->date=$community->getCreation();
      }
    $results=array_merge($folders, $items,$communities,$users);
      
    switch ($order)
      {
      case 'name':
        $this->Component->Sortdao->field='name';
        $this->Component->Sortdao->order='asc';
        usort($results, array($this->Component->Sortdao,'sortByName'));
        break;
      case 'date':
        $this->Component->Sortdao->field='date';
        $this->Component->Sortdao->order='asc';
        usort($results, array($this->Component->Sortdao,'sortByDate'));
        break;
      case 'view':
        $this->Component->Sortdao->field='view';
        $this->Component->Sortdao->order='desc';
        usort($results, array($this->Component->Sortdao,'sortByNumber'));
        break;
      default:
        throw new Zend_Exception('Error order parameter');
        break;
      }
    $resultsArray=array();
    foreach ($results as $result)
      {
      $tmp=$result->toArray();
      if($result instanceof UserDao)
        {
        $tmp['resultType']='user';
        $tmp['formattedDate']=$this->Component->Date->formatDate($result->getCreation());
        }
      if($result instanceof ItemDao)
        {
        $tmp['resultType']='item';
        $tmp['formattedDate']=$this->Component->Date->formatDate($result->getDate());
        }
      if($result instanceof CommunityDao)
        {
        $tmp['resultType']='community';
        $tmp['formattedDate']=$this->Component->Date->formatDate($result->getCreation());
        }
      if($result instanceof FolderDao)
        {
        $tmp['resultType']='folder';
        $tmp['formattedDate']=$this->Component->Date->formatDate($result->getDate());
        }
      unset($tmp['password']);
      unset($tmp['email']);
      $resultsArray[]=$tmp;
      }
    return $resultsArray;
    }//formatResults
    
    
  /** search live Action */
  public function liveAction()
    {
    // This is necessary in order to avoid session lock and being able to run two 
    // ajax requests simultaneously
    session_write_close();

    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
      
    $search = $this->getRequest()->getParam('term');
    $shareSearch = $this->getRequest()->getParam('shareSearch'); //return user group and communities
    
    if(isset ($shareSearch))
      {
      $ItemsDao = array();
      $FoldersDao = array();

      // Search for the communities
      $CommunitiesDao = $this->Community->getCommunitiesFromSearch($search,$this->userSession->Dao); 
      
      // Search for the groups
      $GroupsDao = $this->Group->getGroupFromSearch($search); 
      foreach($GroupsDao as $key=>$group)
        {
        if(strpos($group->getName(), 'group of community')!=false)
          {
          unset($GroupsDao[$key]);
          continue;
          }
        if(isset($this->userSession->Dao)&&$this->userSession->Dao->isAdmin())
          {
          continue;
          }
        $community=$group->getCommunity();
        $GroupsDao[$key]->community=$community;
        if(!$this->Community->policyCheck($community,$this->userSession->Dao))
          {
          unset($GroupsDao[$key]);
          }
        }
      // Search for the users
      $UsersDao = $this->User->getUsersFromSearch($search,$this->userSession->Dao); 
      }
    else
      {
      // Search for the items
      $ItemsDao = $this->ItemKeyword->getItemsFromSearch($search,$this->userSession->Dao);

      // Search for the folders
      $FoldersDao = $this->Folder->getFoldersFromSearch($search,$this->userSession->Dao); 

      // Search for the communities
      $CommunitiesDao = $this->Community->getCommunitiesFromSearch($search,$this->userSession->Dao); 

      // Search for the users
      $UsersDao = $this->User->getUsersFromSearch($search,$this->userSession->Dao); 
      $GroupsDao=array();
      }

    
    // Compute how many of each we should display
    $nitems = count($ItemsDao);
    $nfolders = count($FoldersDao);
    $ncommunities = count($CommunitiesDao);
    $ngroups = count($GroupsDao);
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
    // Groups
    foreach($GroupsDao as $groupDao)
      {
      if($n == $ngroups)
        {
        break;  
        }  
      if($id>1)
        {
        echo ',';    
        }
      echo '{'; 
      echo '"id":"'.$id.'"'; 
      echo ', "label":"'.$groupDao->getName();
      echo '"';       
      echo ', "value":"'.$groupDao->getName().'"'; 
      echo ', "groupid":"'.$groupDao->getKey().'"'; 
      echo ', "category":"Groups"';   
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
    }
    
} // end class

  