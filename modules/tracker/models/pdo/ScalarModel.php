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
require_once BASE_PATH.'/modules/tracker/models/base/ScalarModelBase.php';

/**
 * Scalar PDO Model
 */
class Tracker_ScalarModel extends Tracker_ScalarModelBase
{
  /**
   * Return all associated items
   */
  public function getResultItems($scalar)
    {
    // TODO return a hash array where key is the label and value is the result item
    }

  /**
   * Delete the scalar (deletes all result item associations as well)
   */
  public function delete($scalar)
    {
    // TODO delete from tracker_scalar2item where scalar_id=$scalar->getKey()
    parent::delete($producer);
    }

  /**
   * Used to overwrite trend points with identical timestamps
   */
  public function deleteByTrendAndTimestamp($trendId, $timestamp)
    {
    Zend_Registry::get('dbAdapter')->delete($this->_name, 'trend_id = '.$trendId.' AND submit_time = \''.$timestamp.'\'');
    }
}
