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

interface MIDASDatabaseInterface
{
  /** generic save*/
  public function save($dataarray);
  /** generic delete*/
  public function delete($dao);
  /** generic get value*/
  public function getValue($var, $key, $dao);
  /** generic get all by key*/
  public function getAllByKey($keys);
} // end interface