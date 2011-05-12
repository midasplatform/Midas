<?php
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