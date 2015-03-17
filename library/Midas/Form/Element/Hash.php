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

/** Extend Zend Form Element Hash to generate a more random hash. */
class Midas_Form_Element_Hash extends Zend_Form_Element_Hash
{
    /** Generate a CSRF token. */
    protected function _generateHash()
    {
        $factory = new \RandomLib\Factory;
        $generator = $factory->getMediumStrengthGenerator();
        $random1 = $generator->generateString(32);
        $random2 = $generator->generateString(32);
        $this->_hash = hash('sha256', $random1.$this->getSalt().$this->getName().$random2);
        $this->setValue($this->_hash);
    }
}
