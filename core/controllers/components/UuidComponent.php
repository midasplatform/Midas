<?php
/** UuidComponent componenet */
class UuidComponent extends AppComponent
{ 
      
  /** Get using id*/
  public function getByUid($uuid)
    {
    $loader = new MIDAS_ModelLoader();
    $model = $loader->loadModel('Community');
    $dao = $model->getByUuid($uuid);
    if($dao != false)
      {
      $dao->resourceType = MIDAS_RESOURCE_COMMUNITY;
      return $dao;
      }
      
    $model = $loader->loadModel('Folder');
    $dao = $model->getByUuid($uuid);
    if($dao != false)
      {
      $dao->resourceType = MIDAS_RESOURCE_FOLDER;
      return $dao;
      }
      
    $model = $loader->loadModel('Item');
    $dao = $model->getByUuid($uuid);
    if($dao != false)
      {
      $dao->resourceType = MIDAS_RESOURCE_ITEM;
      return $dao;
      }
      
    $model = $loader->loadModel('ItemRevision');
    $dao = $model->getByUuid($uuid);
    if($dao != false)
      {
      $dao->resourceType = MIDAS_RESOURCE_REVISION;
      return $dao;
      }
      
    $model = $loader->loadModel('User');
    $dao = $model->getByUuid($uuid);
    if($dao != false)
      {
      $dao->resourceType = MIDAS_RESOURCE_USER;
      return $dao;
      }      
    return false;
    }
} // end class
