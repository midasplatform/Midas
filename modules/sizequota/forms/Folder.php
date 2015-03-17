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

/** Folder form for the sizequota module. */
class Sizequota_Form_Folder extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('sizequota_folder');
        $this->setAction($this->getView()->baseUrl('/sizequota/folder/submit'));
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('FDXuUnSDkUE7Anh2kqgca8zv');
        $csrf->setDecorators(array('ViewHelper'));

        $folderId = new Zend_Form_Element_Hidden('folder_id');
        $folderId->setDecorators(array('ViewHelper'));

        $useDefaultFolderQuota = new Zend_Form_Element_Checkbox('use_default_folder_quota');
        $useDefaultFolderQuota->setLabel('Use Default Folder Quota');

        $folderQuotaValue = new Zend_Form_Element_Text('folder_quota_value');
        $folderQuotaValue->setLabel('Quota');
        $folderQuotaValue->addValidator('Float', true);
        $folderQuotaValue->addValidator('Between', true, array('min' => 0, 'max' => PHP_INT_MAX));

        $folderQuotaUnit = new Zend_Form_Element_Select('folder_quota_unit');
        $folderQuotaUnit->setLabel('Unit');
        $folderQuotaUnit->setRequired(true);
        $folderQuotaUnit->addValidator('NotEmpty', true);
        $folderQuotaUnit->addMultiOptions(array(
            MIDAS_SIZE_B => 'B',
            MIDAS_SIZE_KB => 'KB',
            MIDAS_SIZE_MB => 'MB',
            MIDAS_SIZE_GB => 'GB',
            MIDAS_SIZE_TB => 'TB',
        ));

        $this->addDisplayGroup(array($useDefaultFolderQuota, $folderQuotaValue, $folderQuotaUnit), 'global');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $folderId, $useDefaultFolderQuota, $folderQuotaValue, $folderQuotaUnit, $submit));
    }
}
