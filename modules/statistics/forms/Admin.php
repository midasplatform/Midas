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

/** Admin form for the statistics module. */
class Statistics_Form_Admin extends Zend_Form
{
    /** Initialize this form. */
    public function init()
    {
        $this->setName('statistics_admin');
        $this->setMethod('POST');

        $csrf = new Midas_Form_Element_Hash('csrf');
        $csrf->setSalt('ppvbBRacZJzNAxTPQgGuRZ8Z');
        $csrf->setDecorators(array('ViewHelper'));

        $piwikUrl = new Zend_Form_Element_Text(STATISTICS_PIWIK_URL_KEY);
        $piwikUrl->setLabel('Piwik URL');
        $piwikUrl->setRequired(true);
        $piwikUrl->addValidator('NotEmpty', true);
        $piwikUrl->addValidator('Callback', true, array('callback' => array('Zend_Uri', 'check')));

        $piwikId = new Zend_Form_Element_Text(STATISTICS_PIWIK_SITE_ID_KEY);
        $piwikId->setLabel('Piwik Site ID');
        $piwikId->setRequired(true);
        $piwikId->addValidator('NotEmpty', true);

        $piwikApiKey = new Zend_Form_Element_Text(STATISTICS_PIWIK_API_KEY_KEY);
        $piwikApiKey->setLabel('Piwik API Key');
        $piwikApiKey->setRequired(true);
        $piwikApiKey->addValidator('NotEmpty', true);

        $ipInfoDbApiKey = new Zend_Form_Element_Text(STATISTICS_IP_INFO_DB_API_KEY_KEY);
        $ipInfoDbApiKey->setLabel('IPInfoDB API Key');
        $ipInfoDbApiKey->setRequired(true);
        $ipInfoDbApiKey->addValidator('NotEmpty', true);

        $sendReports = new Zend_Form_Element_Checkbox(STATISTICS_SEND_DAILY_REPORTS_KEY);
        $sendReports->setLabel('Send Daily Reports');

        $this->addDisplayGroup(array($piwikUrl, $piwikId, $piwikApiKey, $ipInfoDbApiKey, $sendReports), 'global');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Save');

        $this->addElements(array($csrf, $piwikUrl, $piwikId, $piwikApiKey, $ipInfoDbApiKey, $sendReports, $submit));
    }
}
