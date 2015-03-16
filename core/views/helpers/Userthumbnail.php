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

/** User thumbnail view helper. */
class Zend_View_Helper_Userthumbnail extends Zend_View_Helper_Abstract
{
    /**
     * User thumbnail view helper.
     *
     * @param string $thumbnail link to thumbnail, if any
     * @param string $id value of id attribute
     */
    public function userthumbnail($thumbnail, $id = '')
    {
        $id = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
        $thumbnail = htmlspecialchars($thumbnail, ENT_QUOTES, 'UTF-8');

        if (empty($thumbnail)) {
            echo '<img id="'.$id.'" class="thumbnailSmall" src="'.$this->view->coreWebroot.'/public/images/icons/unknownUser.png" alt="" />';
        } elseif (preg_match("@^https?://@", $thumbnail)) {
            echo '<img id="'.$id.'" class="thumbnailSmall" src="'.$thumbnail.'" alt="" />';
        } else {
            echo '<img id="'.$id.'" class="thumbnailSmall" src="'.$this->view->webroot.'/'.$thumbnail.'" alt="" />';
        }
    }
}
