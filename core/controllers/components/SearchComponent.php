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

/** Search component */
class SearchComponent extends AppComponent
{
    /**
     * Search all the results.
     *
     * @param UserDao $userDao
     * @param string $search
     * @param string $order
     * @return array
     */
    public function searchAll($userDao, $search, $order)
    {
        /** @var ItemModel $itemModel */
        $itemModel = MidasLoader::loadModel('Item');

        /** @var FolderModel $folderModel */
        $folderModel = MidasLoader::loadModel('Folder');

        /** @var CommunityModel $communityModel */
        $communityModel = MidasLoader::loadModel('Community');

        /** @var UserModel $userModel */
        $userModel = MidasLoader::loadModel('User');

        $ItemsDao = $itemModel->getItemsFromSearch($search, $userDao, 200, false, $order);

        // Search for the folders
        $FoldersDao = $folderModel->getFoldersFromSearch($search, $userDao, 15, false, $order);

        // Search for the communities
        $CommunitiesDao = $communityModel->getCommunitiesFromSearch($search, $userDao, 15, false, $order);

        // Search for the users
        $UsersDao = $userModel->getUsersFromSearch($search, $userDao, 15, false, $order);

        $return = array();

        $return['nitems'] = count($ItemsDao);
        $return['nfolders'] = count($FoldersDao);
        $return['ncommunities'] = count($CommunitiesDao);
        $return['nusers'] = count($UsersDao);
        $return['results'] = $this->_formatResults($order, $ItemsDao, $FoldersDao, $CommunitiesDao, $UsersDao);

        return $return;
    }

    /**
     * Format search results.
     *
     * @param string $order
     * @param array $items
     * @param array $folders
     * @param array $communities
     * @param array $users
     * @return array
     * @throws Zend_Exception
     */
    private function _formatResults($order, $items, $folders, $communities, $users)
    {
        foreach ($users as $key => $user) {
            $users[$key]->name = $user->getLastname();
            $users[$key]->date_update = $user->getCreation();
        }
        foreach ($communities as $key => $community) {
            $communities[$key]->date_update = $community->getCreation();
        }
        $results = array_merge($folders, $items, $communities, $users);

        Zend_Loader::loadClass('SortdaoComponent', BASE_PATH.'/core/controllers/components');
        Zend_Loader::loadClass('DateComponent', BASE_PATH.'/core/controllers/components');

        $sortdaoComponent = new SortdaoComponent();
        $dateComponent = new DateComponent();

        switch ($order) {
            case 'relevance':
                $sortdaoComponent->field = 'score';
                $sortdaoComponent->field2 = 'name';
                $sortdaoComponent->order = 'desc';
                $sortdaoComponent->order2 = 'asc';
                foreach ($results as $key => $result) {
                    if (!isset($result->score)) {
                        $results[$key]->score = 1;
                    }
                }
                usort($results, array($sortdaoComponent, 'sortByNumberThenName'));
                break;
            case 'name':
                $sortdaoComponent->field = 'name';
                $sortdaoComponent->order = 'asc';
                usort($results, array($sortdaoComponent, 'sortByName'));
                break;
            case 'date':
                $sortdaoComponent->field = 'date_update';
                $sortdaoComponent->order = 'asc';
                usort($results, array($sortdaoComponent, 'sortByDate'));
                break;
            case 'view':
                $sortdaoComponent->field = 'view';
                $sortdaoComponent->order = 'desc';
                usort($results, array($sortdaoComponent, 'sortByNumber'));
                break;
            default:
                throw new Zend_Exception('Error in order parameter, not one of the expected values.');
                break;
        }
        $resultsArray = array();
        foreach ($results as $result) {
            $tmp = $result->toArray();
            if ($result instanceof UserDao) {
                $tmp['resultType'] = 'user';
                $tmp['formattedDate'] = $dateComponent->formatDate($result->getCreation());
            }
            if ($result instanceof ItemDao) {
                $tmp['resultType'] = 'item';
                $tmp['formattedDate'] = $dateComponent->formatDate($result->getDateUpdate());
            }
            if ($result instanceof CommunityDao) {
                $tmp['resultType'] = 'community';
                $tmp['formattedDate'] = $dateComponent->formatDate($result->getCreation());
            }
            if ($result instanceof FolderDao) {
                $tmp['resultType'] = 'folder';
                $tmp['formattedDate'] = $dateComponent->formatDate($result->getDateUpdate());
            }
            unset($tmp['password']);
            unset($tmp['email']);
            $resultsArray[] = $tmp;
        }

        return $resultsArray;
    }
}
