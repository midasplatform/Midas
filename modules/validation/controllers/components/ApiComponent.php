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
  private function _checkKeys($keys,$values)
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

    return array('name' => $dao->getName(),
                 'description' => $dao->getDescription());
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

    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Dashboard', 'validation');
    $dao = $model->load($value['dashboard_id']);
    $folderModel = $modelLoad->loadModel('Folder');
    if($folderModel->load($value['folder_id']))
      {
      $dao->setTestingFolderId($value['folder_id']);
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

    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Dashboard', 'validation');
    $dao = $model->load($value['dashboard_id']);
    $folderModel = $modelLoad->loadModel('Folder');
    if($folderModel->load($value['folder_id']))
      {
      $dao->setTruthFolderId($value['folder_id']);
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

    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Dashboard', 'validation');
    $dao = $model->load($value['dashboard_id']);
    $folderModel = $modelLoad->loadModel('Folder');
    if($folderModel->load($value['folder_id']))
      {
      $dao->setTrainingFolderId($value['folder_id']);
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

    $modelLoad = new MIDAS_ModelLoader();
    $model = $modelLoad->loadModel('Dashboard', 'validation');
    $dao = $model->load($value['dashboard_id']);
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
    return array('dashboard_id' => $dao->getKey());
    }

} // end class
