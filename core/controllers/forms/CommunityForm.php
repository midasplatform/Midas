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

/** Community forms*/
class CommunityForm extends AppForm
{
  /** create create community form */
  public function createCreateForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/community/create')
          ->setMethod('post');

    $name = new Zend_Form_Element_Text('name');
    $name ->setRequired(true)
          ->addValidator('NotEmpty', true);

    $description = new Zend_Form_Element_Textarea('description');

    $privacy = new Zend_Form_Element_Radio('privacy');
    $privacy->addMultiOptions(array(
                 MIDAS_COMMUNITY_PRIVATE => $this->t("Private, only member can see the community"),
                 MIDAS_COMMUNITY_PUBLIC => $this->t("Public, everyone can see the community"),
                  ))
            ->setRequired(true)
            ->setValue(MIDAS_COMMUNITY_PUBLIC);

    $canJoin = new Zend_Form_Element_Radio('canJoin');
    $canJoin->addMultiOptions(array(
                 MIDAS_COMMUNITY_CAN_JOIN => $this->t("Everyone can join the community"),
                 MIDAS_COMMUNITY_INVITATION_ONLY => $this->t("Only invited users can join the community"),
                  ))
            ->setValue(MIDAS_COMMUNITY_CAN_JOIN);

    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel($this->t("Create"));

    $form->addElements(array($name, $description, $privacy, $submit, $canJoin));
    return $form;
    }

  /** create a group*/
  public function createCreateGroupForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/community/manage')
          ->setMethod('post');

    $name = new Zend_Form_Element_Text('name');
    $name ->setRequired(true)
          ->addValidator('NotEmpty', true);

    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel($this->t("Save"));

    $form->addElements(array($name, $submit));
    return $form;
    }

} // end class
?>
