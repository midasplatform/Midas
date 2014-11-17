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

/**
 * Admin controller for the landingpage module.
 *
 * @property Landingpage_TextModel $Landingpage_Text
 */
class Landingpage_AdminController extends Landingpage_AppController
{
    /** @var array */
    public $_models = array('Setting');

    /** @var array */
    public $_moduleDaos = array('Text');

    /** @var array */
    public $_moduleModels = array('Text');

    /** Index action */
    public function indexAction()
    {
        $this->requireAdminPrivileges();

        $this->view->pageTitle = 'Landing Page Module Configuration';
        $form = new Landingpage_Form_Admin();
        $textDaos = $this->Landingpage_Text->getAll();

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();

            if ($form->isValid($data)) {
                $values = $form->getValues();

                if (count($textDaos) > 0) {
                    $textDao = $textDaos[0];
                } else {
                    /** @var Landingpage_TextDao $textDao */
                    $textDao = MidasLoader::newDao('Text', $this->moduleName);
                }

                $textDao->setText($values[LANDINGPAGE_TEXT_KEY]);
                $this->Landingpage_Text->save($textDao);
                unset($values[LANDINGPAGE_TEXT_KEY]);

                foreach ($values as $key => $value) {
                    $this->Setting->setConfig($key, $value, $this->moduleName);
                }
            }

            $form->populate($data);
        } else {
            $elements = $form->getElements();

            foreach ($elements as $element) {
                $name = $element->getName();

                if ($name === LANDINGPAGE_TEXT_KEY) {
                    if (count($textDaos) > 0) {
                        $value = $textDaos[0]->getText();
                        $form->setDefault($name, $value);
                    }
                } elseif ($name !== 'csrf' && $name !== 'submit') {
                    $value = $this->Setting->getValueByName($name, $this->moduleName);

                    if (!is_null($value)) {
                        $form->setDefault($name, $value);
                    }
                }
            }
        }

        $this->view->form = $form;
        session_start();
    }
}
