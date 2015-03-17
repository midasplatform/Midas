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

/** Admin form for the sizequota module. */
class Sizequota_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('sizequota_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('f6g5NzqPWAunkSykbBpmTmpH');
        $csrf->setDecorators(array('ViewHelper'));

        $defaultUserQuotaValue = new Zend_Form_Element_Text(MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_VALUE_KEY);
        $defaultUserQuotaValue->setLabel('Default User Quota');
        $defaultUserQuotaValue->addValidator('Float', true);
        $defaultUserQuotaValue->addValidator('Between', true, array('min' => 0, 'max' => PHP_INT_MAX));

        $defaultUserQuotaUnit = new Zend_Form_Element_Select(MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_UNIT_KEY);
        $defaultUserQuotaUnit->setLabel('Unit');
        $defaultUserQuotaUnit->setRequired(true);
        $defaultUserQuotaUnit->addValidator('NotEmpty', true);
        $defaultUserQuotaUnit->addMultiOptions(array(
            MIDAS_SIZE_B => 'B',
            MIDAS_SIZE_KB => 'KB',
            MIDAS_SIZE_MB => 'MB',
            MIDAS_SIZE_GB => 'GB',
            MIDAS_SIZE_TB => 'TB',
        ));

        $this->addDisplayGroup(array($defaultUserQuotaValue, $defaultUserQuotaUnit), 'default_user_quota');

        $defaultCommunityQuotaValue = new Zend_Form_Element_Text(MIDAS_SIZEQUOTA_DEFAULT_COMMUNITY_QUOTA_VALUE_KEY);
        $defaultCommunityQuotaValue->setLabel('Default Community Quota');
        $defaultCommunityQuotaValue->addValidator('Float', true);
        $defaultCommunityQuotaValue->addValidator('Between', true, array('min' => 0, 'max' => PHP_INT_MAX));

        $defaultCommunityQuotaUnit = new Zend_Form_Element_Select(MIDAS_SIZEQUOTA_DEFAULT_COMMUNITY_QUOTA_UNIT_KEY);
        $defaultCommunityQuotaUnit->setLabel('Unit');
        $defaultCommunityQuotaUnit->setRequired(true);
        $defaultCommunityQuotaUnit->addValidator('NotEmpty', true);
        $defaultCommunityQuotaUnit->addMultiOptions(array(
            MIDAS_SIZE_B => 'B',
            MIDAS_SIZE_KB => 'KB',
            MIDAS_SIZE_MB => 'MB',
            MIDAS_SIZE_GB => 'GB',
            MIDAS_SIZE_TB => 'TB',
        ));

        $this->addDisplayGroup(array($defaultCommunityQuotaValue, $defaultCommunityQuotaUnit), 'default_community_quota');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $defaultUserQuotaValue, $defaultUserQuotaUnit, $defaultCommunityQuotaValue, $defaultCommunityQuotaUnit, $submit));
    }
}
