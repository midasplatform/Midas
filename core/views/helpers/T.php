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

/** Translation view helper. */
class Zend_View_Helper_T extends Zend_View_Helper_Abstract
{
    /**
     * Translation view helper.
     *
     * @param string $text text
     * @return string translated text if available, otherwise the input text
     */
    public function t($text)
    {
        Zend_Loader::loadClass("InternationalizationComponent", BASE_PATH.'/core/controllers/components');

        return htmlspecialchars(InternationalizationComponent::translate($text), ENT_QUOTES, 'UTF-8');
    }
}
