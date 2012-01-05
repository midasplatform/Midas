<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

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

/** sizequota config form */
class Sizequota_ConfigForm extends AppForm
{
  /** create the admin->modules page config form */
  public function createConfigForm()
    {
    $form = new Zend_Form;
    $form->setAction($this->webroot.'/sizequota/config/index')
         ->setMethod('post');

    $defaultUserQuota = new Zend_Form_Element_Text('defaultuserquota');
    $defaultCommunityQuota = new Zend_Form_Element_Text('defaultcommunityquota');

    $submit = new Zend_Form_Element_Submit('submitConfig');
    $submit->setLabel('Save configuration');

    $form->addElements(array($defaultUserQuota, $defaultCommunityQuota, $submit));
    return $form;
    }

  /** form used to set a folder-specific quota */
  public function createFolderForm($defaultQuota)
    {
    if($defaultQuota === '')
      {
      $defaultQuota = $this->t('Unlimited');
      }
    $form = new Zend_Form;
    $form->setAction($this->webroot.'/sizequota/config/foldersubmit')
         ->setMethod('post');

    $submit = new Zend_Form_Element_Submit('submitQuota');
    $submit->setLabel($this->t('Save'));

    $useDefault = new Zend_Form_Element_Radio('usedefault');
    $useDefault->addMultiOptions(array(MIDAS_USE_DEFAULT_QUOTA => $this->t('Use the default quota: ').$defaultQuota,
                                       MIDAS_USE_SPECIFIC_QUOTA => $this->t('Use a specific quota:')))
               ->setRequired(true);

    $quota = new Zend_Form_Element_Text('quota');
    $quota->setAttrib('quota', 30);

    $form->addElements(array($useDefault, $quota, $submit));
    return $form;
    }

} // end class
?>
