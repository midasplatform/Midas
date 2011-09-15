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
class FilterComponent extends AppComponent
{
  /** get a filter*/
  public function getFilter($filter)
    {
    Zend_Loader::loadClass($filter, BASE_PATH.'/core/controllers/components/filters');
    if(!class_exists($filter))
      {
      throw new Zend_Exception("Unable to load filter: ".$filter );
      }
    return new $filter();
    }
} // end class