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

/** Assetstore Model Base*/
abstract class AssetstoreModelBase extends AppModel
{
  /** Constructor*/
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'assetstore';
    $this->_key = 'assetstore_id';

    $this->_mainData = array(
        'assetstore_id' =>  array('type' => MIDAS_DATA),
        'name' =>  array('type' => MIDAS_DATA),
        'itemrevision_id' =>  array('type' => MIDAS_DATA),
        'path' =>  array('type' => MIDAS_DATA),
        'type' =>  array('type' => MIDAS_DATA),
        'bitstreams' =>  array('type' => MIDAS_ONE_TO_MANY, 'model' => 'Bitstream', 'parent_column' => 'assetstore_id', 'child_column' => 'assetstore_id'),
        );
    $this->initialize(); // required
    } // end __construct()

  /** Abstract functions */
  abstract function getAll();

  /**
   * helper function to detect a collision between a row with the
   * passed in keyVal (which should be null for a new row), and any
   * existing rows with the column $var having the value $value.
   * @param type $var column name
   * @param type $value column value
   * @param type $keyVal key value for passed in row, or null if a new row
   * @return type
   */
  protected function detectCollision($var, $value, $keyVal)
    {
    // detect whether the current Assetstore will collide for the
    // variable value with an existing row
    $found = $this->findBy($var, $value);
    if(isset($found) && sizeof($found) > 0)
      {
      foreach($found as $existingAssetstoreDao)
        {
        // if the current dao does not already exist
        // then it collides with an existing row
        if(empty($keyVal))
          {
          return true;
          }
        else
          {
          // the current dao does already exist
          if($existingAssetstoreDao->getKey() !== $keyVal)
            {
            // if the current dao does exist and it is different than
            // the existingAssestoreDao then
            // it is a collision with an existing other row
            return true;
            }
          else
            {
            // if the current dao does exist and it shares the same key
            // it is updating itself, so there is no collision
            return false;
            }
          }
        }
      }
    else
      {
      // no matching rows found, no collision
      return false;
      }
    }



  /** save an assetsore*/
  public function save($dao)
    {
    $key = $this->_key;
    // get the keyValue of the dao that is to be saved
    if(isset($dao->$key))
      {
      // the dao has a keyValue
      $keyVal = $dao->$key;
      }
    else
      {
      // the dao is for a new row, and doesn't yet have a keyValue
      $keyVal = null;
      }
    $path = $dao->getPath();
    if(empty($path) || $this->detectCollision('path', $path, $keyVal))
      {
      throw new Zend_Exception('Assetstore paths must be unique');
      }
    $name = $dao->getName();
    if(empty($name) || $this->detectCollision('name', $name, $keyVal))
      {
      throw new Zend_Exception('Assetstore names must be unique');
      }
    parent::save($dao);
    }

  /** delete an assetstore (and all the items in it)*/
  public function delete($dao)
    {
    if(!$dao instanceof AssetstoreDao)
      {
      throw new Zend_Exception("Error param.");
      }
    $bitreams = $dao->getBitstreams();
    $items = array();
    foreach($bitreams as $key => $bitstream)
      {
      $revision = $bitstream->getItemrevision();
      if(empty($revision))
        {
        continue;
        }
      $item = $revision->getItem();

      if(empty($item))
        {
        continue;
        }

      $items[$item->getKey()] = $item;
      }

    $modelLoader = new MIDAS_ModelLoader();
    $item_model = $modelLoader->loadModel('Item');
    foreach($items as $item)
      {
      $item_model->delete($item);
      }
    parent::delete($dao);
    }// delete

  /**
   * This function returns the default assetstore in the database. This
   * is assumed to be named "Default" and is selected as such. If there
   * is no assetsore we fail misserably, if there are more than one then
   * we only return the "first."
   * @return the default assetstore
   */
  public function getDefault()
    {
    $found = $this->findBy('name', 'Default');
    return $found[0];
    } // end getDefault

} // end class AssetstoreModelBase
