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

require_once BASE_PATH.'/core/models/base/FeedModelBase.php';

/**
 * Pdo Model
 */
class FeedModel extends FeedModelBase
{
    /**
     * Get feed by resource.
     *
     * @param array $typeArray
     * @param MIDAS_GlobalDao $dao
     * @return array
     * @throws Zend_Exception
     */
    public function getFeedByResourceAndType($typeArray, $dao)
    {
        if (!is_array($typeArray)) {
            $typeArray = array($typeArray);
        }
        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('p' => 'feed'))->where(
            'resource = ?',
            (string) $dao->getKey()
        );

        $rowset = $this->database->fetchAll($sql);
        $feeds = array();
        foreach ($rowset as $row) {
            $feed = $this->initDao('Feed', $row);
            if (in_array($feed->getType(), $typeArray)) {
                $feeds[] = $this->initDao('Feed', $row);
            }
        }

        return $feeds;
    }

    /**
     * Check if the policy is valid.
     *
     * @param FeedDao $feedDao
     * @param null|UserDao $userDao
     * @param int $policy
     * @return bool
     * @throws Zend_Exception
     */
    public function policyCheck($feedDao, $userDao = null, $policy = 0)
    {
        if (!$feedDao instanceof FeedDao || !is_numeric($policy)) {
            throw new Zend_Exception("Error in params when checking Feed Policy.");
        }
        if ($userDao == null) {
            $userId = -1;
        } elseif (!$userDao instanceof UserDao) {
            throw new Zend_Exception("Should be an user.");
        } else {
            $userId = $userDao->getUserId();
            if ($userDao->isAdmin()) {
                return true;
            }
        }

        $subqueryUser = $this->database->select()->setIntegrityCheck(false)->from(
            array('p' => 'feedpolicyuser'),
            array('feed_id')
        )->where(
            'policy >= ?',
            $policy
        )->where('p.feed_id = ?', $feedDao->getKey())->where('user_id = ? ', $userId);

        $subqueryGroup = $this->database->select()->setIntegrityCheck(false)->from(
            array('p' => 'feedpolicygroup'),
            array('feed_id')
        )->where(
            'policy >= ?',
            $policy
        )->where('p.feed_id = ?', $feedDao->getKey())->where(
            '( '.$this->database->getDB()->quoteInto('group_id = ? ', MIDAS_GROUP_ANONYMOUS_KEY).' OR
                              group_id IN ('.new Zend_Db_Expr(
                $this->database->select()->setIntegrityCheck(false)->from(
                    array('u2g' => 'user2group'),
                    array('group_id')
                )->where('u2g.user_id = ?', $userId).'))'
            )
        );

        $sql = $this->database->select()->union(array($subqueryUser, $subqueryGroup));

        $row = $this->database->fetchRow($sql);
        if ($row == null) {
            return false;
        }

        return true;
    }

    /**
     * Get feeds.
     *
     * @param UserDao $loggedUserDao
     * @param null|UserDao $userDao
     * @param null|CommunityDao $communityDao
     * @param int $policy
     * @param int $limit
     * @return array
     * @throws Zend_Exception
     */
    protected function getFeeds($loggedUserDao, $userDao = null, $communityDao = null, $policy = 0, $limit = 20)
    {
        $isAdmin = false;
        if ($loggedUserDao == null) {
            $userId = -1;
        } elseif (!$loggedUserDao instanceof UserDao) {
            throw new Zend_Exception("Should be an user.");
        } else {
            $userId = $loggedUserDao->getUserId();
            if ($loggedUserDao->isAdmin()) {
                $isAdmin = true;
            }
        }

        if ($userDao != null && !$userDao instanceof UserDao) {
            throw new Zend_Exception("Should be an user.");
        }

        if ($communityDao != null && !$communityDao instanceof CommunityDao) {
            throw new Zend_Exception("Should be a community.");
        }

        $sql = $this->database->select()->setIntegrityCheck(false)->from(array('f' => 'feed'))->limit($limit);

        if (!$isAdmin) {
            $sql->joinLeft(
                array('fpu' => 'feedpolicyuser'),
                '
                    f.feed_id = fpu.feed_id AND '.$this->database->getDB()->quoteInto('fpu.policy >= ?', $policy).'
                       AND '.$this->database->getDB()->quoteInto('fpu.user_id = ? ', $userId).' ',
                array('userpolicy' => 'fpu.policy')
            )->joinLeft(
                array('fpg' => 'feedpolicygroup'),
                '
                          f.feed_id = fpg.feed_id AND '.$this->database->getDB()->quoteInto(
                    'fpg.policy >= ?',
                    $policy
                ).'
                             AND ( '.$this->database->getDB()->quoteInto(
                    'fpg.group_id = ? ',
                    MIDAS_GROUP_ANONYMOUS_KEY
                ).' OR
                                  fpg.group_id IN ('.new Zend_Db_Expr(
                    $this->database->select()->setIntegrityCheck(false)->from(
                        array('u2g' => 'user2group'),
                        array('group_id')
                    )->where('u2g.user_id = ?', $userId)
                ).'))',
                array('grouppolicy' => 'fpg.policy')
            )->where(
                '(
            fpu.feed_id is not null or
            fpg.feed_id is not null)'
            );
        }

        if ($userDao != null) {
            $sql->where('f.user_id = ? ', $userDao->getKey());
        }

        if ($communityDao != null) {
            $sql->join(
                array('f2c' => 'feed2community'),
                $this->database->getDB()->quoteInto(
                    'f2c.community_id = ? ',
                    $communityDao->getKey()
                ).' AND f.feed_id = f2c.feed_id',
                array()
            );
        }
        $sql->order(array('f.date DESC'));
        $rowset = $this->database->fetchAll($sql);
        $rowsetAnalysed = array();
        foreach ($rowset as $row) {
            if (isset($row['userpolicy']) && $row['userpolicy'] == null) {
                $row['userpolicy'] = 0;
            }
            if (isset($row['grouppolicy']) && $row['grouppolicy'] == null) {
                $row['grouppolicy'] = 0;
            }
            if (!isset($rowsetAnalysed[$row['feed_id']]) || ($rowsetAnalysed[$row['feed_id']]->policy < $row['userpolicy'] && $rowsetAnalysed[$row['feed_id']]->policy < $row['grouppolicy'])) {
                $tmpDao = $this->initDao('Feed', $row);
                if ((isset($row['userpolicy']) && isset($row['grouppolicy'])) && $row['userpolicy'] >= $row['grouppolicy']) {
                    $tmpDao->policy = $row['userpolicy'];
                } elseif ($isAdmin) {
                    $tmpDao->policy = MIDAS_POLICY_ADMIN;
                } else {
                    $tmpDao->policy = $row['grouppolicy'];
                }
                $rowsetAnalysed[$row['feed_id']] = $tmpDao;
                unset($tmpDao);
            }
        }
        $this->Component->Sortdao->field = 'date';
        $this->Component->Sortdao->order = 'asc';
        usort($rowsetAnalysed, array($this->Component->Sortdao, 'sortByDate'));

        return $rowsetAnalysed;
    }

    /**
     * Add a community to a feed.
     *
     * @param FeedDao $feed
     * @param CommunityDao $community
     * @throws Zend_Exception
     */
    public function addCommunity($feed, $community)
    {
        if (!$community instanceof CommunityDao) {
            throw new Zend_Exception("Should be a community.");
        }
        if (!$feed instanceof FeedDao) {
            throw new Zend_Exception("Should be an feed.");
        }
        $this->database->link('communities', $feed, $community);
    }

    /**
     * Delete DAO.
     *
     * @param FeedDao $feedDao
     */
    public function delete($feedDao)
    {
        $feedpolicygroups = $feedDao->getFeedpolicygroup();

        /** @var FeedpolicygroupModel $feedpolicygroupModel */
        $feedpolicygroupModel = MidasLoader::loadModel('Feedpolicygroup');
        foreach ($feedpolicygroups as $f) {
            $feedpolicygroupModel->delete($f);
        }

        $feedpolicyuser = $feedDao->getFeedpolicyuser();

        /** @var FeedpolicyuserModel $feedpolicyuserModel */
        $feedpolicyuserModel = MidasLoader::loadModel('Feedpolicyuser');
        foreach ($feedpolicyuser as $f) {
            $feedpolicyuserModel->delete($f);
        }

        $communities = $feedDao->getCommunities();
        foreach ($communities as $c) {
            $this->database->removeLink('communities', $feedDao, $c);
        }
        parent::delete($feedDao);
    }
}
