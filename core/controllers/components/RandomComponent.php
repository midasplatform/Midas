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

/** Random component for generating random numbers. */
class RandomComponent extends AppComponent
{
    /** @var \RandomLib\Factory */
    protected $_factory = null;

    /** @var \RandomLib\Generator */
    protected $_generator = null;

    /**
     * Generate a medium-strength random integer within the given range.
     *
     * @param int $minimum lower bound of the range
     * @param int $maximum upper bound of the range
     * @return int
     */
    public function generateInt($minimum = 0, $maximum = PHP_INT_MAX)
    {
        if (is_null($this->_factory)) {
            $this->_factory = new \RandomLib\Factory();
            $this->_generator = $this->_factory->getMediumStrengthGenerator();
        }

        return $this->_generator->generateInt($minimum, $maximum);
    }

    /**
     * Generate a medium-strength random string of the given length.
     *
     * @param int $length length of the generated string
     * @param string $characters characters to use to generate the string
     * @return string
     */
    public function generateString($length, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        if (is_null($this->_factory)) {
            $this->_factory = new \RandomLib\Factory();
            $this->_generator = $this->_factory->getMediumStrengthGenerator();
        }

        return $this->_generator->generateString($length, $characters);
    }
}
