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

/** ItemKeywordModelBase */
class ItemKeywordModelBase extends AppModel
{
  /** Contructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'itemkeyword';
    $this->_daoName = 'ItemKeywordDao';
    $this->_key = 'keyword_id';

    $this->_mainData = array(
      'keyword_id' => array('type' => MIDAS_DATA),
      'value' => array('type' => MIDAS_DATA),
      'relevance' => array('type' => MIDAS_DATA),
      );
    $this->initialize(); // required
    } // end __construct()  
  

} // end class ItemKeywordModelBase