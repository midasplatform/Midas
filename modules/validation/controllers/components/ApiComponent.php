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

/** Component for api methods */
class Validation_ApiComponent extends AppComponent
{
  /**
   * Description is here
   * @param foo seven times 5
   * @param bar twist blarg
   * @return The word BAR
   */
  public function test($value)
    {
    return array('foo'=> $value['foo'],
                 'bar'=> $value['bar']);
    }

  /**
   * Just a words function
   */
  public function words()
    {
    return array('x' => 'y');
    }

} // end class
