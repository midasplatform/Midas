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

/** User forms*/
class UserForm extends AppForm
{
  /** create login form */
  public function createLoginForm()
    {
    $form = new Zend_Form;

    $form->setAction($this->webroot.'/user/login')
          ->setMethod('post')
          ->setAttrib('id', 'loginForm');

    $email = new Zend_Form_Element_Text('email');
    $email
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->addValidator('EmailAddress');

    $password = new Zend_Form_Element_Password('password');
    $password
             ->addValidator('NotEmpty', true)
             ->setRequired(true);
    $rememberMe = new Zend_Form_Element_Checkbox('remerberMe');

    if(isset($this->uri))
      {
      $url = new Zend_Form_Element_Hidden("url");
      $form->addElement($url);
      }

    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel($this->t("Login"))
            ->setAttrib('class', 'globalButton');

    $form->addElements(array($email, $password, $rememberMe, $submit));
    return $form;
    }

  /** register  form */
  public function createRegisterForm()
    {
    $form = new Zend_Form;
    $form->setAction($this->webroot.'/user/register')
          ->setMethod('post')
          ->setAttrib('id', 'registerForm')
          ->setAttrib('class', 'genericForm');


    $email = new Zend_Form_Element_Text('email');
    $email->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->setAttrib('maxLength', 255)
          ->addValidator('EmailAddress');


    $firstname = new Zend_Form_Element_Text('firstname');
    $firstname
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->setAttrib('maxLength', 255);

    $lastname = new Zend_Form_Element_Text('lastname');
    $lastname
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->setAttrib('maxLength', 255);

    $password1 = new Zend_Form_Element_Password('password1');
    $password1
             ->addValidator('NotEmpty', true)
             ->setRequired(true);

    $password2 = new Zend_Form_Element_Password('password2');
    $password2
             ->addValidator('NotEmpty', true)
             ->setRequired(true);

    $condiftions = new Zend_Form_Element_Checkbox('conditions');

    $submit = new  Zend_Form_Element_Submit('submit');
    $submit ->setLabel($this->t("Register"));

    $form->addElements(array($email, $firstname, $lastname, $password1, $password2, $condiftions, $submit));

    return $form;
    }


  /** acount  form */
  public function createAccountForm($defaultValue = array())
    {
    $form = new Zend_Form;
    $form->setAction($this->webroot.'/user/settings')
          ->setMethod('post');

    $firstname = new Zend_Form_Element_Text('firstname');
    $firstname
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->setAttrib('maxLength', 255);

    $lastname = new Zend_Form_Element_Text('lastname');
    $lastname
          ->setRequired(true)
          ->addValidator('NotEmpty', true)
          ->setAttrib('maxLength', 255);

    $company = new Zend_Form_Element_Text('company');
    $company
          ->setAttrib('maxLength', 255);

    $city = new Zend_Form_Element_Text('city');
    $city
          ->setAttrib('maxLength', 100);

    $country = new Zend_Form_Element_Text('country');
    $country
          ->setAttrib('maxLength', 100);

    $validator = new Zend_Validate_Callback(array('Zend_Uri', 'check'));
    $website = new Zend_Form_Element_Text('website');
    $website
          ->setAttrib('maxLength', 255)
          ->addValidator($validator);

    $biography = new Zend_Form_Element_Textarea('biography');
    $biography
          ->addValidator(new Zend_Validate_Alnum());

    $submit = new  Zend_Form_Element_Submit('modifyAccount');
    $submit ->setLabel($this->t("Modify"));

    $privacy = new Zend_Form_Element_Radio('privacy');
    $privacy->addMultiOptions(array(
                 MIDAS_USER_PUBLIC => $this->t("Public (Anyone can see my information)"),
                 MIDAS_USER_PRIVATE => $this->t("Private (Nobody can see my information)"),
                  ))
          ->setRequired(true)
          ->setValue(MIDAS_COMMUNITY_PUBLIC);

    if(isset($defaultValue['firstname']))
      {
      $firstname->setValue($defaultValue['firstname']);
      }
    if(isset($defaultValue['lastname']))
      {
      $lastname->setValue($defaultValue['lastname']);
      }
    if(isset($defaultValue['company']))
      {
      $company->setValue($defaultValue['company']);
      }
    if(isset($defaultValue['privacy']))
      {
      $privacy->setValue($defaultValue['privacy']);
      }
    if(isset($defaultValue['city']))
      {
      $city->setValue($defaultValue['city']);
      }
    if(isset($defaultValue['country']))
      {
      $country->setValue($defaultValue['country']);
      }
    if(isset($defaultValue['website']))
      {
      $website->setValue($defaultValue['website']);
      }
    if(isset($defaultValue['biography']))
      {
      $biography->setValue($defaultValue['biography']);
      }

    $form->addElements(array($website, $city, $country, $biography, $firstname, $lastname, $company, $privacy, $submit));

    return $form;
    }
} // end class