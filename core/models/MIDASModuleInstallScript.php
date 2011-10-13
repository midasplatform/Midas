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
 * This is the base class for any script that should run when a module
 * is installed for the first time.
 * Subclasses may implement two callbacks, preInstall() and postInstall()
 */
class MIDASModuleInstallScript
{
  /** constructor */
  public function __construct()
    {
    }

  /** preInstall gets called before the tables are installed */
  public function preInstall()
    {
    }

  /** postInstall gets called after the tables are installed */
  public function postInstall()
    {
    }

} //end class MIDASModuleInstallScript
?>
