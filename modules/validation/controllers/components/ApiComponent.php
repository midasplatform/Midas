<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/

/** Component for api methods */
class Validation_ApiComponent extends AppComponent
{

  /**
   * Helper function for verifying keys in an input array
   */
  private function _checkKeys($keys, $values)
    {
    foreach($keys as $key)
      {
      if(!array_key_exists($key, $values))
        {
        throw new Exception('Parameter '.$key.' must be set.', -1);
        }
      }
    }

  /**
   * Get the name of the requested dashboard
   * @param dashboard_id the id of the dashboard
   * @return the name of the dashboard
   */
  public function getDashboard($value)
    {
    $this->_checkKeys(array('dashboard_id'), $value);

    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Dashboard', 'validation');
    $dao = $model->load($value['dashboard_id']);

    if(!$dao)
      {
      throw new Exception('No dashboard found with that id.', -1);
      }

    return array('dashboard_id' => $dao->getKey(),
                 'owner_id' => $dao->getOwnerId(),
                 'name' => $dao->getName(),
                 'description' => $dao->getDescription(),
                 'truthfolder_id' => $dao->getTruthfolderId(),
                 'testingfolder_id' => $dao->getTestingfolderId(),
                 'trainingfolder_id' => $dao->getTrainingfolderId(),
                 );
    }

  /**
   * Get all available validation dashboards
   * @return an array of validation dashboards
   */
  public function getAllDashboards($value)
    {
    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Dashboard', 'validation');
    $model->loadDaoClass('DashboardDao', 'validation');
    $daos = $model->getAll();

    $results = array();
    foreach($daos as $dao)
      {
      $results[] = array('dashboard_id' => $dao->getKey(),
                         'owner_id' => $dao->getOwnerId(),
                         'name' => $dao->getName(),
                         'description' => $dao->getDescription(),
                         'truthfolder_id' => $dao->getTruthfolderId(),
                         'testingfolder_id' => $dao->getTestingfolderId(),
                         'trainingfolder_id' => $dao->getTrainingfolderId());
      }
    return $results;
    }

  /**
   * Create a dashboard with the given name and description
   * @param name the name of the new dashboard
   * @param description the name of the new dashboard
   * @return the id of the created dashboard
   */
  public function createDashboard($value)
    {
    $this->_checkKeys(array('name', 'description'), $value);

    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    $userDao = $authComponent->getUser($value,
                                       Zend_Registry::get('userSession')->Dao);
    if(!$userDao || !$userDao->isAdmin())
      {
      throw new Exception('Only an admin can create a dashboard.', -1);
      }

    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Dashboard', 'validation');
    $model->loadDaoClass('DashboardDao', 'validation');
    $dao = new Validation_DashboardDao();
    $dao->setName($value['name']);
    $dao->setDescription($value['description']);
    $model->save($dao);

    return array('dashboard_id' => $dao->getKey());
    }

  /**
   * Associate a folder as testing data
   * @param dashboard_id the id of the target dashboard
   * @param folder_id the id of the testing folder
   * @return the id of the created dashboard
   */
  public function setTestingFolder($value)
    {
    $this->_checkKeys(array('dashboard_id', 'folder_id'), $value);

    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    $userDao = $authComponent->getUser($value,
                                       Zend_Registry::get('userSession')->Dao);
    if(!$userDao || !$userDao->isAdmin())
      {
      throw new Exception('Only an admin can set the testing folder.', -1);
      }

    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Dashboard', 'validation');
    $dao = $model->load($value['dashboard_id']);
    $folderModel = $modelLoad->loadModel('Folder');
    if($folderModel->load($value['folder_id']))
      {
      $dao->setTestingfolderId($value['folder_id']);
      $model->save($dao);
      }
    else
      {
      throw new Exception('No folder found with that id.', -1);
      }
    return array('dashboard_id' => $dao->getKey());
    }

  /**
   * Associate a folder as truth data
   * @param dashboard_id the id of the target dashboard
   * @param folder_id the id of the truth folder
   * @return the id of the created dashboard
   */
  public function setTruthFolder($value)
    {
    $this->_checkKeys(array('dashboard_id', 'folder_id'), $value);

    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    $userDao = $authComponent->getUser($value,
                                       Zend_Registry::get('userSession')->Dao);
    if(!$userDao || !$userDao->isAdmin())
      {
      throw new Exception('Only an admin can set the truth folder.', -1);
      }

    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Dashboard', 'validation');
    $dao = $model->load($value['dashboard_id']);
    $folderModel = $modelLoad->loadModel('Folder');
    if($folderModel->load($value['folder_id']))
      {
      $dao->setTruthfolderId($value['folder_id']);
      $model->save($dao);
      }
    else
      {
      throw new Exception('No folder found with that id.', -1);
      }
    return array('dashboard_id' => $dao->getKey());
    }

  /**
   * Associate a folder as training data
   * @param dashboard_id the id of the target dashboard
   * @param folder_id the id of the testing folder
   * @return the id of the created dashboard
   */
  public function setTrainingFolder($value)
    {
    $this->_checkKeys(array('dashboard_id', 'folder_id'), $value);

    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    $userDao = $authComponent->getUser($value,
                                       Zend_Registry::get('userSession')->Dao);
    if(!$userDao || !$userDao->isAdmin())
      {
      throw new Exception('Only an admin can set the training folder.', -1);
      }

    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Dashboard', 'validation');
    $dao = $model->load($value['dashboard_id']);
    $folderModel = $modelLoad->loadModel('Folder');
    if($folderModel->load($value['folder_id']))
      {
      $dao->setTrainingfolderId($value['folder_id']);
      $model->save($dao);
      }
    else
      {
      throw new Exception('No folder found with that id.', -1);
      }
    return array('dashboard_id' => $dao->getKey());
    }

  /**
   * Associate a folder as a result set
   * @param dashboard_id the id of the target dashboard
   * @param folder_id the id of the result folder
   * @return the id of the created dashboard
   */
  public function addResultFolder($value)
    {
    $this->_checkKeys(array('dashboard_id', 'folder_id'), $value);

    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    $userDao = $authComponent->getUser($value,
                                       Zend_Registry::get('userSession')->Dao);
    if(!$userDao)
      {
      throw new Exception('You must login to submit a result folder.', -1);
      }

    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Dashboard', 'validation');
    $dao = $model->load($value['dashboard_id']);
    $folderModel = $modelLoad->loadModel('Folder');
    $folderDao = $folderModel->load($value['folder_id']);
    if($folderDao)
      {
      $model->addResult($dao, $folderDao);
      }
    else
      {
      throw new Exception('No folder found with that id.', -1);
      }
    return array('dashboard_id' => $dao->getKey());
    }

  /**
   * Remove a result folder
   * @param dashboard_id the id of the target dashboard
   * @param folder_id the id of the result folder to be removed
   * @return the id of the created dashboard
   */
  public function removeResultFolder($value)
    {
    $this->_checkKeys(array('dashboard_id', 'folder_id'), $value);

    // Verify that the user is an admin
    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    $userDao = $authComponent->getUser($value,
                                       Zend_Registry::get('userSession')->Dao);
    if(!$userDao || !$userDao->isAdmin())
      {
      throw new Exception('Only administrators can remove results.', -1);
      }

    // Load the dashboard
    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Dashboard', 'validation');
    $dao = $model->load($value['dashboard_id']);
    if(!$dao)
      {
      throw new Exception('No dashboard found with that id.', -1);
      }

    // disassociate the folder with the dashboard as a result
    $folderModel = $modelLoad->loadModel('Folder');
    $folderDao = $folderModel->load($value['folder_id']);
    if($folderDao)
      {
      $model->removeResult($dao, $folderDao);
      }
    else
      {
      throw new Exception('No folder found with that id.', -1);
      }

    // Return the id of the dashboard if things go according to plan
    return array('dashboard_id' => $dao->getKey());
    }

  /**
   * Get the result folders associated with a dashboard
   * @param dashboard_id the id of the target dashboard
   * @return and array of folder_id's corresponding to result folders assigned
   *         to the dashboard
   */
  public function getResultFolders($value)
    {
    $this->_checkKeys(array('dashboard_id'), $value);

    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Dashboard', 'validation');
    $dao = $model->load($value['dashboard_id']);
    if(!$dao)
      {
      throw new Exception('No dashboard found with that id.', -1);
      }

    $results = $dao->getResults();
    $return = array();
    foreach($results as $result)
      {
      $return[] = $result->getKey();
      }
    return array('dashboard_id' => $dao->getKey(),
                 'results' => $return);
    }

  /**
   * Set a single scalar result value
   * @param dashboard_id the id of the target dashboard
   * @param folder_id the id of the target result folder
   * @param item_id the id of the result item
   * @param value the value of the result being set
   * @return the id of the created scalar result
   */
  public function setScalarResult($value)
    {
    // check for the proper parameters
    $this->_checkKeys(array('dashboard_id', 'folder_id', 'item_id', 'value'),
                      $value);

    // Verify authentication (only admins can set results)
    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    $userDao = $authComponent->getUser($value,
                                       Zend_Registry::get('userSession')->Dao);
    if(!$userDao || !$userDao->isAdmin())
      {
      throw new Exception('Only administrators can write result scalars.', -1);
      }

    // Load the necessary models
    $modelLoad = new MIDAS_ModelLoader();
    $dashboardModel = $modelLoad->loadModel('Dashboard', 'validation');
    $scalarResultModel = $modelLoad->loadModel('ScalarResult', 'validation');
    $itemModel = $modelLoad->loadModel('Item');
    $folderModel = $modelLoad->loadModel('Folder');

    // Verify that the dashboard exists
    $dashboardDao = $dashboardModel->load($value['dashboard_id']);
    if(!$dashboardDao)
      {
      throw new Exception('No dashboard found with that id.', -1);
      }

    // Verify that the folder_id is associated with the dashboard as a result
    $results = $dashboardDao->getResults();
    $tgtResult = null;
    foreach($results as $result)
      {
      if($result->getKey() == $value['folder_id'])
        {
        $tgtResult = $result;
        break;
        }
      }
    if(!$tgtResult)
      {
      throw new Exception('No result found with that folder_id.', -1);
      }

    // Verify that an item with item_id is in that result set
    $resultItems = $tgtResult->getItems();
    $tgtItem = null;
    foreach($resultItems as $resultItem)
      {
      if($resultItem->getKey() == $value['item_id'])
        {
        $tgtItem = $resultItem;
        }
      }
    if(!$tgtItem)
      {
      throw new Exception('No result item found with that item_id.', -1);
      }

    // Assuming everything went according to plan, set the result scalar
    $dashboardModel->setScore($dashboardDao,
                              $tgtResult,
                              $tgtItem,
                              $value['value']);
    return array('dashboard_id' => $dashboardDao->getKey());
    }

  /**
   * Get a single scalar result value
   * @param dashboard_id the id of the target dashboard
   * @param folder_id the id of the target result folder
   * @param item_id the id of the result item
   * @return the id of the created scalar result
   */
  public function getScalarResult($value)
    {
    // check for the proper parameters
    $this->_checkKeys(array('dashboard_id', 'folder_id', 'item_id'),
                      $value);

    // Load the necessary models
    $modelLoad = new MIDAS_ModelLoader();
    $dashboardModel = $modelLoad->loadModel('Dashboard', 'validation');
    $scalarResultModel = $modelLoad->loadModel('ScalarResult', 'validation');
    $itemModel = $modelLoad->loadModel('Item');
    $folderModel = $modelLoad->loadModel('Folder');

    // Verify that the dashboard exists
    $dashboardDao = $dashboardModel->load($value['dashboard_id']);
    if(!$dashboardDao)
      {
      throw new Exception('No dashboard found with that id.', -1);
      }

    // Verify that the folder_id is associated with the dashboard as a result
    $results = $dashboardDao->getResults();
    $tgtResult = null;
    foreach($results as $result)
      {
      if($result->getKey() == $value['folder_id'])
        {
        $tgtResult = $result;
        break;
        }
      }
    if(!$tgtResult)
      {
      throw new Exception('No result found with that folder_id.', -1);
      }

    $itemId = $value['item_id'];
    $scores = $dashboardModel->getScores($dashboardDao, $tgtResult);
    if(!isset($scores[$itemId]))
      {
      throw new Exception('No scalar found for that item_id.', -1);
      }

    return array('item_id' => $itemId,
                 'value' => $scores[$itemId]);
    }

  /**
   * Get the scalar results associated with one result folder
   * @param dashboard_id the id of the target dashboard
   * @param folder_id the id of the target result folder
   * @return the scalar results for the specified folder
   */
  public function getScores($value)
    {
    // check for the proper parameters
    $this->_checkKeys(array('dashboard_id', 'folder_id'), $value);

    // Load the necessary models
    $modelLoad = new MIDAS_ModelLoader();
    $dashboardModel = $modelLoad->loadModel('Dashboard', 'validation');
    $folderModel = $modelLoad->loadModel('Folder');

    // Verify that the dashboard exists
    $dashboardDao = $dashboardModel->load($value['dashboard_id']);
    if(!$dashboardDao)
      {
      throw new Exception('No dashboard found with that id.', -1);
      }

    // Verify that the folder_id is associated with the dashboard as a result
    $results = $dashboardDao->getResults();
    $tgtResult = null;
    foreach($results as $result)
      {
      if($result->getKey() == $value['folder_id'])
        {
        $tgtResult = $result;
        break;
        }
      }
    if(!$tgtResult)
      {
      throw new Exception('No result found with that folder_id.', -1);
      }

    $scores = $dashboardModel->getScores($dashboardDao, $tgtResult);

    return array('dashboard_id' => $dashboardDao->getKey(),
                 'scores' => $scores);
    }

  /**
   * Get all scalar results associated with a dashboard
   * @param dashboard_id the id of the target dashboard
   * @return the scalar results for the specified dashboard
   */
  public function getAllScores($value)
    {
    // check for the proper parameters
    $this->_checkKeys(array('dashboard_id', 'folder_id'), $value);

    // Load the necessary models
    $modelLoad = new MIDAS_ModelLoader();
    $dashboardModel = $modelLoad->loadModel('Dashboard', 'validation');

    // Verify that the dashboard exists
    $dashboardDao = $dashboardModel->load($value['dashboard_id']);
    if(!$dashboardDao)
      {
      throw new Exception('No dashboard found with that id.', -1);
      }

    $scores = $dashboardModel->getAllScores($dashboardDao);

    return array('dashboard_id' => $dashboardDao->getKey(),
                 'scores' => $scores);
    }

} // end class
