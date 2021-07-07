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

/** Admin form for the comments module. */
class Comments_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('comment_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('qsJm32258fFFcBRjbSHHu628');
        $csrf->setDecorators(array('ViewHelper'));

        $sort = new Zend_Form_Element_Select('sortSelect');
        $sort->addMultiOption(COMMENTS_OLDEST_FIRST, 'Oldest First');
        $sort->addMultiOption(COMMENTS_NEWEST_FIRST, 'Newest First');
        $sort->setLabel('Sort Comments by');
        $sort->setRequired(true);
        $sort->addValidator('NotEmpty', true);

        $this->addDisplayGroup(array($sort), 'global');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $sort, $submit));
    }
}
