<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

/** Component for api methods */
class Thumbnailcreator_ApiComponent extends AppComponent
{
    /** Return the user dao */
    private function _callModuleApiMethod($args, $coreApiMethod, $resource = null, $hasReturn = true)
    {
        $ApiComponent = MidasLoader::loadComponent('Api'.$resource, 'thumbnailcreator');
        $rtn = $ApiComponent->$coreApiMethod($args);
        if ($hasReturn) {
            return $rtn;
        }

        return null;
    }

    /**
     * Create a big thumbnail for the given bitstream with the given width. It is used as the main image of the given item and shown in the item view page.
     *
     * @param bitstreamId The bitstream to create the thumbnail from
     * @param itemId The item to set the thumbnail on
     * @param width (Optional) The width in pixels to resize to (aspect ratio will be preserved). Defaults to 575
     * @return The ItemthumbnailDao object that was created
     */
    public function createBigThumbnail($args)
    {
        return $this->_callModuleApiMethod($args, 'createBigThumbnail', 'item');
    }

    /**
     * Create a 100x100 small thumbnail for the given item. It is used for preview purpose and displayed in the 'preview' and 'thumbnails' sidebar sections.
     *
     * @param itemId The item to set the thumbnail on
     * @return The Item object (with the new thumbnail_id) and the path where the newly created thumbnail is stored
     */
    public function createSmallThumbnail($args)
    {
        return $this->_callModuleApiMethod($args, 'createSmallThumbnail', 'item');
    }
}
