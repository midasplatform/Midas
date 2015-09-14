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
 * Module DAO.
 *
 * @method int getModuleId()
 * @method void setModuleId(int $moduleId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getCurrentMajorVersion()
 * @method void setCurrentMajorVersion(string $currentMajorVersion)
 * @method string getCurrentMinorVersion()
 * @method void setCurrentMinorVersion(string $currentMinorVersion)
 * @method string getCurrentPatchVersion()
 * @method void setCurrentPatchVersion(string $currentPatchVersion)
 * @method int getEnabled()
 * @method void setEnabled(int $enabled)
 */
class ModuleDao extends AppDao
{
    /** @var string */
    public $_model = 'Module';

    /**
     * Return the current version of this module.
     *
     * @return string current version of this module.
     */
    public function getCurrentVersion()
    {
        return $this->getCurrentMajorVersion().'.'.$this->getCurrentMinorVersion().'.'.$this->getCurrentPatchVersion();
    }

    /**
     * Set the current version of this module.
     *
     * @param string $currentVersion current version of this module.
     * @throws Zend_Exception
     */
    public function setCurrentVersion($currentVersion)
    {
        $result = preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/i', $currentVersion, $matches);
        if ($result !== 1) {
            throw new Zend_Exception('Invalid current version string.');
        }
        $this->setCurrentMajorVersion($matches[1]);
        $this->setCurrentMinorVersion($matches[2]);
        $this->setCurrentPatchVersion($matches[3]);
    }

    /**
     * Return true if this module is enabled.
     *
     * @return bool true if this module is enabled, false otherwise.
     */
    public function isEnabled()
    {
        return $this->getEnabled() === 1;
    }
}
