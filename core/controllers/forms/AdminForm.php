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

/** Admin forms */
class AdminForm extends AppForm
{
    /** create form */
    public function createConfigForm()
    {
        $form = new Zend_Form();
        $form->setAction($this->webroot.'/admin/index')->setMethod('post');

        $title = new Zend_Form_Element_Text('title');
        $title->setRequired(true)->addValidator('NotEmpty', true);

        $description = new Zend_Form_Element_Textarea('description');

        $language = new Zend_Form_Element_Select('language');
        $language->addMultiOptions(array('en' => 'English', 'fr' => 'French'));

        $timeZone = new Zend_Form_Element_Select('time_zone');
        $timeZone->addMultiOptions(
            array(
                'America/Anchorage' => 'America/Anchorage',
                'America/Chicago' => 'America/Chicago',
                'America/Denver' => 'America/Denver',
                'America/Los_Angeles' => 'America/Los Angeles',
                'America/New_York' => 'America/New York',
                'America/Phoenix' => 'America/Phoenix',
                'Europe/London' => 'Europe/London',
                'Europe/Paris' => 'Europe/Paris',
                'Pacific/Honolulu' => 'Pacific/Honolulu',
                'UTC' => 'UTC',
            )
        );

        $dynamicHelp = new Zend_Form_Element_Checkbox('dynamic_help');
        $allowPasswordReset = new Zend_Form_Element_Checkbox('allow_password_reset');
        $gravatar = new Zend_Form_Element_Checkbox('gravatar');
        $closeRegistration = new Zend_Form_Element_Checkbox('close_registration');

        $submit = new Zend_Form_Element_Submit('submitConfig');
        $submit->setLabel('Save configuration');

        $form->addElements(
            array(
                $title,
                $description,
                $language,
                $timeZone,
                $dynamicHelp,
                $allowPasswordReset,
                $closeRegistration,
                $gravatar,
                $submit,
            )
        );

        return $form;
    }
}
