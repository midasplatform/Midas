<?php
/**
 * \class ItempolicygroupModel
 * \brief Pdo Model
 */
class ItempolicygroupModel extends MIDASItempolicygroupModel
{
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
      $this->database->delete($this->getPolicy($group,$item));
      }
    $this->loadDaoClass('ItempolicygroupDao');
    $policyGroupDao=new ItempolicygroupDao();
    $policyGroupDao->setGroupId($group->getGroupId());
    $policyGroupDao->setItemId($item->getItemId());
    $policyGroupDao->setPolicy($policy);
    $this->database->save($policyGroupDao);
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
    return $this->initDao('Itempolicygroup',$this->database->fetchRow($this->database->select()->where('item_id = ?',$item->getKey())->where('group_id = ?',$group->getKey())));
    }
} // end class
?>
