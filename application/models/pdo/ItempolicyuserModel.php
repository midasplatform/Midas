<?php
/**
 * \class ItempolicyuserModel
 * \brief Pdo Model
 */
class ItempolicyuserModel extends AppModelPdo
  {
  public $_name='itempolicyuser';

  public $_mainData=array(
    'item_id'=>array(
    'type'=>MIDAS_DATA
  ),'user_id'=>array(
    'type'=>MIDAS_DATA
  ),'policy'=>array(
    'type'=>MIDAS_DATA
  ),'item'=>array(
    'type'=>MIDAS_MANY_TO_ONE,'model'=>'Item','parent_column'=>'item_id','child_column'=>'item_id'
  ),'user'=>array(
    'type'=>MIDAS_MANY_TO_ONE,'model'=>'User','parent_column'=>'user_id','child_column'=>'user_id'
  )
  );

  /** create a policy
   * @return ItempolicyuserDao*/
  public function createPolicy($user, $item, $policy)
    {
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    if(!is_numeric($policy))
      {
      throw new Zend_Exception("Should be a number.");
      }
    if(!$user->saved && !$item->saved)
      {
      throw new Zend_Exception("Save the daos first.");
      }
    if($this->getPolicy($user,$item) !== false)
      {
      $this->delete($this->getPolicy($user,$item));
      }
    $this->loadDaoClass('ItempolicyuserDao');
    $policyUser=new ItempolicyuserDao();
    $policyUser->setUserId($user->getUserId());
    $policyUser->setItemId($item->getItemId());
    $policyUser->setPolicy($policy);
    $this->save($policyUser);
    return $policyUser;
    }

  /** getPolicy
   * @return ItempolicyuserDao
   */
  public function getPolicy($user, $item)
    {
    if(!$user instanceof UserDao)
      {
      throw new Zend_Exception("Should be a user.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    return $this->initDao('Itempolicyuser',$this->fetchRow($this->select()->where('item_id = ?',$item->getKey())->where('user_id = ?',$user->getKey())));
    }
  }
?>
