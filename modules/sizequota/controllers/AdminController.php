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

/** Admin controller for the sizequota module. */
class Sizequota_AdminController extends Sizequota_AppController
{
    /** @var array */
    public $_models = array('Setting');

    /** Index action */
    public function indexAction()
    {
        $this->requireAdminPrivileges();

        $this->view->pageTitle = 'Size Quota Module Configuration';
        $form = new Sizequota_Form_Admin();

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();

            if ($form->isValid($data)) {
                $values = $form->getValues();

                if (!is_null($values[MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_VALUE_KEY]) && !is_null(MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_UNIT_KEY)) {
                    if ($values[MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_VALUE_KEY] === '') {
                        $this->Setting->setConfig(
                            MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_KEY,
                            '',
                            $this->moduleName
                        );
                    } else {
                        $this->Setting->setConfig(
                            MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_KEY,
                            round($values[MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_VALUE_KEY] * $values[MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_UNIT_KEY]),
                            $this->moduleName
                        );
                    }
                }

                if (!is_null($values[MIDAS_SIZEQUOTA_DEFAULT_COMMUNITY_QUOTA_VALUE_KEY]) && !is_null(MIDAS_SIZEQUOTA_DEFAULT_COMMUNITY_QUOTA_UNIT_KEY)) {
                    if ($values[MIDAS_SIZEQUOTA_DEFAULT_COMMUNITY_QUOTA_VALUE_KEY] === '') {
                        $this->Setting->setConfig(
                            MIDAS_SIZEQUOTA_DEFAULT_COMMUNITY_QUOTA_KEY,
                            '',
                            $this->moduleName
                        );
                    } else {
                        $this->Setting->setConfig(
                            MIDAS_SIZEQUOTA_DEFAULT_COMMUNITY_QUOTA_KEY,
                            round($values[MIDAS_SIZEQUOTA_DEFAULT_COMMUNITY_QUOTA_VALUE_KEY] * $values[MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_UNIT_KEY]),
                            $this->moduleName
                        );
                    }
                }
            }

            $form->populate($data);
        } else {
            $defaultUserQuota = $this->Setting->getValueByName(MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_KEY, $this->moduleName);

            if (!is_null($defaultUserQuota)) {
                $defaultUserQuotaValueAndUnit = self::computeQuotaValueAndUnit($defaultUserQuota);
                $form->setDefault(MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_VALUE_KEY, $defaultUserQuotaValueAndUnit['value']);
                $form->setDefault(MIDAS_SIZEQUOTA_DEFAULT_USER_QUOTA_UNIT_KEY, $defaultUserQuotaValueAndUnit['unit']);
            }

            $defaultCommunityQuota = $this->Setting->getValueByName(MIDAS_SIZEQUOTA_DEFAULT_COMMUNITY_QUOTA_KEY, $this->moduleName);

            if (!is_null($defaultCommunityQuota)) {
                $defaultCommunityQuotaValueAndUnit = self::computeQuotaValueAndUnit($defaultCommunityQuota);

                $form->setDefault(MIDAS_SIZEQUOTA_DEFAULT_COMMUNITY_QUOTA_VALUE_KEY, $defaultCommunityQuotaValueAndUnit['value']);
                $form->setDefault(MIDAS_SIZEQUOTA_DEFAULT_COMMUNITY_QUOTA_UNIT_KEY, $defaultCommunityQuotaValueAndUnit['unit']);
            }
        }

        $this->view->form = $form;
        session_start();
    }

    /**
     * Compute quota value and unit.
     *
     * @param int|string $bytes
     * @return array
     */
    protected static function computeQuotaValueAndUnit($bytes)
    {
        if ($bytes >= MIDAS_SIZE_TB) {
            return array('value' => $bytes / MIDAS_SIZE_TB, 'unit' => MIDAS_SIZE_TB);
        }

        if ($bytes >= MIDAS_SIZE_GB) {
            return array('value' => $bytes / MIDAS_SIZE_GB, 'unit' => MIDAS_SIZE_GB);
        }

        if ($bytes >= MIDAS_SIZE_MB) {
            return array('value' => $bytes / MIDAS_SIZE_MB, 'unit' => MIDAS_SIZE_MB);
        }

        if ($bytes >= MIDAS_SIZE_KB) {
            return array('value' => $bytes / MIDAS_SIZE_KB, 'unit' => MIDAS_SIZE_KB);
        }

        if ($bytes > 0) {
            return array('value' => $bytes, 'unit' => MIDAS_SIZE_TB);
        }

        return array('value' => '', 'unit' => MIDAS_SIZE_MB);
    }
}
