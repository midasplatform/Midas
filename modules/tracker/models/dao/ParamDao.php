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

/**
 * Param DAO for the tracker module.
 *
 * @method int getParamId()
 * @method void setParamId(int $paramId)
 * @method int getScalarId()
 * @method void setScalarId(int $scalarId)
 * @method int getParamName()
 * @method void setParamName(int $paramName)
 * @method int getParamType()
 * @method void setParamType(int $paramType)
 * @method int getTextValue()
 * @method void setTextValue(int $textValue)
 * @method int getNumericValue()
 * @method void setNumericValue(int $numericValue)
 */
class Tracker_ParamDao extends Tracker_AppDao
{
    /** @var string */
    public $_model = 'Param';

    /** @var string */
    public $_module = 'tracker';

    /**
     * Set the value of the param, which will be either stored as a numeric
     * value if the paramValue can be coerced to numeric or else a text value.
     *
     * @param string $paramValue value to be set for this param
     */
    public function setParamValue($paramValue)
    {
        if (!empty($paramValue) && is_numeric($paramValue)) {
            $this->setParamType('numeric');
            $this->setNumericValue(floatval($paramValue));
        } else {
            $this->setParamType('text');
            $this->setTextValue($paramValue);
        }
    }

    /**
     * Get the value of the param, regardless of its type, returning either a
     * numeric or a string.
     *
     * @return float|int|string.
     */
    public function getParamValue()
    {
        if ($this->getParamType() === 'numeric') {
            return $this->getNumericValue();
        }

        return $this->getTextValue();
    }
}
