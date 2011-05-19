<?php
/** Feed policy model base */
abstract class UniqueidentifierModelBase extends AppModel
{
  /** Constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'uniqueidentifier';

    $this->_mainData = array(
        'uniqueidentifier_id' => array('type' => MIDAS_DATA),
        'resource_type' => array('type' => MIDAS_DATA),
        'resource_id' => array('type' => MIDAS_DATA),
      );
    $this->initialize(); // required
    } // end __construct()
    
  abstract function getIndentifier($dao);
  abstract function getByUid($uid);

  /** Get resource */
  public function getResource($dao)
    {
    if(!$dao instanceof UniqueidentifierDao)
      {
      throw new Zend_Exception('Error Parameter');
      }
    $type = $dao->getResourceType();
    $modelLoad = new MIDAS_ModelLoader();
    switch($type)
      {
      case MIDAS_RESOURCE_ASSETSTORE:
        $model = $modelLoad->loadModel('Assetstore');
        break;
      case MIDAS_RESOURCE_BITSTREAM:
        $model = $modelLoad->loadModel('Bitstream');
        break;
      case MIDAS_RESOURCE_ITEM:
        $model = $modelLoad->loadModel('Item');
        break;
      case MIDAS_RESOURCE_COMMUNITY:
        $model = $modelLoad->loadModel('Community');
        break;
      case MIDAS_RESOURCE_REVISION:
        $model = $modelLoad->loadModel('ItemRevision');
        break;
      case MIDAS_RESOURCE_FOLDER:
        $model = $modelLoad->loadModel('Folder');
        break;
      case MIDAS_RESOURCE_USER:
        $model = $modelLoad->loadModel('User');
        break;
      default :
        throw new Zend_Exception("Undefined type");
      }
    return $model->load($dao->getResourceId());
    }
    
  /** Create a new entry */
  public function newUUID($dao)
    {
    $uuDao = $this->getIndentifier($dao);
    if($uuDao != false)
      {
      return $uuDao;
      }
    $type = $this->_getType($dao);
    
    $this->loadDaoClass('UniqueidentifierDao');
    $uuDao = new UniqueidentifierDao();
    $uuDao->setResourceType($type);
    $uuDao->setResourceId($dao->getKey());
    $this->save($uuDao);
    return $uuDao;
    }//end newUUID
  
  /** get type*/
  protected function _getType($dao)
    {
    if($dao instanceof AssetstoreDao)
      {
      $type = MIDAS_RESOURCE_ASSETSTORE;
      }
    if($dao instanceof BitstreamDao)
      {
      $type = MIDAS_RESOURCE_BITSTREAM;
      }
    if($dao instanceof ItemDao)
      {
      $type = MIDAS_RESOURCE_ITEM;
      }
    if($dao instanceof CommunityDao)
      {
      $type = MIDAS_RESOURCE_COMMUNITY;
      }
    if($dao instanceof ItemRevisionDao)
      {
      $type = MIDAS_RESOURCE_REVISION;
      }
    if($dao instanceof FolderDao)
      {
      $type = MIDAS_RESOURCE_FOLDER;
      }
    if($dao instanceof UserDao)
      {
      $type = MIDAS_RESOURCE_USER;
      }
    if(!isset($type))
      {
      throw new Zend_Exception('Undefined resource');
      }
    return $type;
    }
  
  /** save */
  public function save($dao)
    {
    if(!$dao instanceof UniqueidentifierDao)
      {
      throw new Zend_Exception('Error Parameter');
      }
    if(!isset($dao->uniqueidentifier_id))
      {
      $dao->uniqueidentifier_id = uniqid() . md5(mt_rand());
      }
    parent::save($dao);
    }// end save
    
} // end class FeedpolicygroupModelBase