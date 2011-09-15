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

/** Sort Daos*/
class SortdaoComponent extends AppComponent
  {
  public $field = '';
  public $order = 'asc';

  /** sort daos*/
  public function sortByDate($a, $b)
    {
    $field = $this->field;
    if($this->field == '' || !isset($a->$field))
      {
      throw new Zend_Exception("Error field.");
      }

    $a_t = strtotime($a->$field );
    $b_t = strtotime($b->$field );

    if($a_t == $b_t)
      {
      return 0;
      }

    if($this->order == 'asc')
      {
      return ($a_t > $b_t ) ? -1 : 1;
      }
    else
      {
      return ($a_t > $b_t ) ? 1 : -1;
      }
    }//end sortByDate

  /** sort by name*/
  public function sortByName($a, $b)
    {
    $field = $this->field;
    if($this->field == '' || !isset($a->$field))
      {
      throw new Zend_Exception("Error field.");
      }
    $a_n = strtolower($a->$field);
    $b_n = strtolower($b->$field);

    if($a_n == $b_n)
      {
      return 0;
      }

    if($this->order == 'asc')
      {
      return ($a_n < $b_n) ? -1 : 1;
      }
    else
      {
      return ($a_n < $b_n ) ? 1 : -1;
      }
    }//end sortByDate

  /** sort by number*/
  public function sortByNumber($a, $b)
    {
    $field = $this->field;
    if($this->field == '' || !isset($a->$field))
      {
      throw new Zend_Exception("Error field.");
      }
    $a_n = strtolower($a->$field);
    $b_n = strtolower($b->$field);

    if($a_n == $b_n)
      {
      return 0;
      }

    if($this->order == 'asc')
      {
      return ($a_n < $b_n) ? -1 : 1;
      }
    else
      {
      return ($a_n < $b_n ) ? 1 : -1;
      }
    }//end sortByNumber

  /** Unique*/
  public function arrayUniqueDao($array, $keep_key_assoc = false)
    {
    $duplicate_keys = array();
    $tmp         = array();

    foreach($array as $key => $val)
      {
      // convert objects to arrays, in_array() does not support objects
      if(is_object($val))
        {
        $val = (array)$val;
        }

      if(!in_array($val, $tmp))
        {
        $tmp[] = $val;
        }
      else
        {
        $duplicate_keys[] = $key;
        }
      }

    foreach($duplicate_keys as $key)
      {
      unset($array[$key]);
      }

    return $keep_key_assoc ? $array : array_values($array);
    }
  } // end class