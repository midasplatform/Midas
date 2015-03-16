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
 * User DAO.
 *
 * @method int getUserId()
 * @method void setUserId(int $UserId)
 * @method string getFirstname()
 * @method void setFirstname(string $firstName)
 * @method string getLastname()
 * @method void setLastname(string $lastName)
 * @method string getEmail()
 * @method void setEmail(string $email)
 * @method string getThumbnail()
 * @method void setThumbnail(string $thumbnail)
 * @method string getCompany()
 * @method void setCompany(string $company)
 * @method string getHashAlg()
 * @method void setHashAlg(string $hashAlg)
 * @method string getSalt()
 * @method void setSalt(string $salt)
 * @method string getCreation()
 * @method void setCreation(string $creation)
 * @method int getFolderId()
 * @method void setFolderId(int $folderId)
 * @method int getAdmin()
 * @method void setAdmin(int $admin)
 * @method int getPrivacy()
 * @method void setPrivacy(int $privacy)
 * @method int getView()
 * @method void setView(int $view)
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getCity()
 * @method void setCity(string $city)
 * @method string getCountry()
 * @method void setCountry(string $country)
 * @method string getWebsite()
 * @method void setWebsite(string $website)
 * @method string getBiography()
 * @method void setBiography(string $biography)
 * @method int getDynamichelp()
 * @method void setDynamichelp(int $dynamicHelp)
 * @method FolderDao getFolder()
 * @method void setFolder(FolderDao $folder)
 * @method array getGroups()
 * @method void setGroups(array $groups)
 * @method array getInvitations()
 * @method void setInvitations(array $invitations)
 * @method array getFolderpolicyuser()
 * @method void setFolderpolicyuser(array $folderPolicyUser)
 * @method array getItempolicyuser()
 * @method void setItempolicyuser(array $itemPolicyUser)
 * @method array getFeeds()
 * @method void setFeeds(array $feeds)
 * @method array getFeedpolicyuser()
 * @method void setFeedpolicyuser(array $feedPolicyUser)
 * @method array getItemrevisions()
 * @method void setItemrevisions(array $itemRevisions)
 * @package Core\DAO
 */
class UserDao extends AppDao
{
    /** @var string */
    public $_model = 'User';

    /**
     * Return true if this user is an administrator.
     *
     * @return bool
     */
    public function isAdmin()
    {
        if ($this->getAdmin() == 1) {
            return true;
        }

        return false;
    }

    /**
     * Return the full name of this user.
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->getFirstname()." ".$this->getLastname();
    }

    /**
     * Return the user DAO field values as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $return = parent::toArray();
        unset($return['password']);
        unset($return['hash_alg']);
        unset($return['salt']);

        return $return;
    }
}
