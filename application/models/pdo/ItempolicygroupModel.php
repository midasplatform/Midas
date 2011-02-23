<?php
/**
 * \class ItempolicygroupModel
 * \brief Pdo Model
 */
class ItempolicygroupModel extends AppModelPdo
  {
  public $_name='itempolicygroup';

  public $_mainData=array(
    'item_id'=>array(
    'type'=>MIDAS_DATA
  ),'group_id'=>array(
    'type'=>MIDAS_DATA
  ),'policy'=>array(
    'type'=>MIDAS_DATA
  ),'item'=>array(
    'type'=>MIDAS_MANY_TO_ONE,'model'=>'Item','parent_column'=>'item_id','child_column'=>'item_id'
  ),'group'=>array(
    'type'=>MIDAS_MANY_TO_ONE,'model'=>'Group','parent_column'=>'group_id','child_column'=>'group_id'
  )
  );

  /** create a policy
   * @return ItempolicygroupDao*/
  public function createPolicy($group, $item, $policy)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    if(!is_numeric($policy))
      {
      throw new Zend_Exception("Should be a number.");
      }
    if(!$group->saved && !$item->saved)
      {
      throw new Zend_Exception("Save the daos first.");
      }
    if($this->getPolicy($group,$item) !== false)
      {
      $this->delete($this->getPolicy($group,$item));
      }
    $this->loadDaoClass('ItempolicygroupDao');
    $policyGroupDao=new ItempolicygroupDao();
    $policyGroupDao->setGroupId($group->getGroupId());
    $policyGroupDao->setItemId($item->getItemId());
    $policyGroupDao->setPolicy($policy);
    $this->save($policyGroupDao);
    return $policyGroupDao;
    }

  /** getPolicy
   * @return ItempolicygroupDao
   */
  public function getPolicy($group, $item)
    {
    if(!$group instanceof GroupDao)
      {
      throw new Zend_Exception("Should be a group.");
      }
    if(!$item instanceof ItemDao)
      {
      throw new Zend_Exception("Should be an item.");
      }
    return $this->initDao('Itempolicygroup',$this->fetchRow($this->select()->where('item_id = ?',$item->getKey())->where('group_id = ?',$group->getKey())));
    }
  }
?>
