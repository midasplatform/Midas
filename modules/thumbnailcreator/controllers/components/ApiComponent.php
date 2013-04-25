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
class Thumbnailcreator_ApiComponent extends AppComponent
{

  /** Return the user dao */
  private function _callModuleApiMethod($args, $coreApiMethod, $resource = null,  $hasReturn = true)
    {
    $ApiComponent = MidasLoader::loadComponent('Api'.$resource, 'thumbnailcreator');
    $rtn = $ApiComponent->$coreApiMethod($args);
    if($hasReturn)
      {
      return $rtn;
      }
    }

  /**
   * Create a big thumbnail for the given bitstream with the given width. It is used as the main image of the given item and shown in the item view page.
   * @param bitstreamId The bitstream to create the thumbnail from
   * @param itemId The item to set the thumbnail on
   * @param width (Optional) The width in pixels to resize to (aspect ratio will be preserved). Defaults to 575
   * @return The ItemthumbnailDao obejct that was created
   */
  public function createBigThumbnail($args)
    {
    return $this->_callModuleApiMethod($args, 'createBigThumbnail', 'item');
    }


/**
   * Create a 100x100 small thumbnail for the given item. It is used for preview purpose and displayed in the 'preview' and 'thumbnails' sidebar sections.
   * @param itemId The item to set the thumbnail on
   * @return The Item obejct (with the new thumbnail_id) and the path where the newly created thumbnail is stored
   */

  public function createSmallThumbnail($args)
    {
    return $this->_callModuleApiMethod($args, 'createSmallThumbnail', 'item');
    }

} // end class
