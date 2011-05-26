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

/**
 * \class TaskModel
 * \brief Pdo Model
 */
class Task_TaskModel extends AppModelPdo
{
  public $_name = 'module_task_task';
  public $_key = 'task_id';

  public $_mainData= array(
      'task_id'=>  array('type'=>MIDAS_DATA),
      'type'=>  array('type'=>MIDAS_DATA),
      'resource_type'=>  array('type'=>MIDAS_DATA),
      'resource_id' =>  array('type'=>MIDAS_DATA),
      'parameters' =>  array('type'=>MIDAS_DATA),
      );
    
  /** create a task
   *
   * @param int $type
   * @param int $resourceType
   * @param int $resourceId
   * @param string $parameters
   * @return TaskDao 
   */
  public function createTask($type,$resourceType,$resourceId,$parameters)
    {
    if(!is_numeric($type)||!is_numeric($resourceId)||!is_numeric($resourceType))
      {
      throw new Zend_Exception('Error paramters');
      }
    $this->loadDaoClass('TaskDao');
    $taskDao=new TaskDao();
    $taskDao->setType($type);
    $taskDao->setResourceType($resourceType);
    $taskDao->setResourceId($resourceId);
    $taskDao->setParameters($parameters);
    parent::save($taskDao);
    return $taskDao;
    }
    
    /** do not use, use method createTask */
  public function save($dao)
    {
    throw new Zend_Exception(" Do not use, use method createTask.");
    }//end Save
    
}  // end class
?>