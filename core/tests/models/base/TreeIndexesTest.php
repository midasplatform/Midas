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
 * This test is for ensuring the integrity of the left and right indexes of
 * our folder tree.
 */
class Core_TreeIndexesTest extends DatabaseTestCase
{
    /** init tests */
    public function setUp()
    {
        Zend_Registry::set('modulesEnable', array());
        Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));

        $this->_models = array('Bitstream', 'Folder', 'Item', 'ItemRevision', 'Progress');
        parent::setUp();

        $this->setupDatabase(array('treeIndexes'));
    }

    /**
     * Make sure that calling remove orphans correctly removes our orphaned folders,
     * and also recomputes the tree indexes and puts the tree in a correct state.
     */
    public function testRemoveOrphansFolder()
    {
        $progress = $this->Progress->createProgress();

        $orphan0 = $this->Folder->load(5000);
        $orphan1 = $this->Folder->load(5001);
        $this->assertTrue($orphan0 instanceof FolderDao);
        $this->assertTrue($orphan1 instanceof FolderDao);

        // Run the operation to cleanup our folder tree
        $this->Folder->removeOrphans($progress);

        // Make sure that orphaned folders were deleted
        $orphan0 = $this->Folder->load(5000);
        $orphan1 = $this->Folder->load(5001);
        $this->assertEquals($orphan0, null);
        $this->assertEquals($orphan1, null);

        // Verify the state of our tree indexes
        $user1 = $this->Folder->load(1001);
        $user1_public = $this->Folder->load(1005);
        $user1_private = $this->Folder->load(1006);
        $user1_topLevel = $this->Folder->load(1013);
        $user1_topLevel_sub0 = $this->Folder->load(1017);

        $user2 = $this->Folder->load(1002);
        $user2_public = $this->Folder->load(1007);
        $user2_private = $this->Folder->load(1008);

        $comm1 = $this->Folder->load(1003);

        $comm2 = $this->Folder->load(1004);
        $comm2_public = $this->Folder->load(1011);
        $comm2_private = $this->Folder->load(1012);
        $comm2_public_sub0 = $this->Folder->load(1018);
        $comm2_public_sub1 = $this->Folder->load(1019);

        $this->_assertSpan($user1, 17);
        $this->_assertSpan($user1_public, 1);
        $this->_assertSpan($user1_private, 9);
        $this->_assertSpan($user1_topLevel, 3);
        $this->_assertNoOverlap($user1_public, $user1_private);
        $this->_assertNoOverlap($user1_public, $user1_topLevel);
        $this->_assertNoOverlap($user1_private, $user1_topLevel);
        $this->_assertDescendant($user1, $user1_public);
        $this->_assertDescendant($user1, $user1_private);
        $this->_assertDescendant($user1, $user1_topLevel);
        $this->_assertDescendant($user1_topLevel, $user1_topLevel_sub0);

        $this->_assertSpan($user2, 5);
        $this->_assertSpan($user2_public, 1);
        $this->_assertSpan($user2_private, 1);
        $this->_assertNoOverlap($user2_public, $user2_private);
        $this->_assertDescendant($user2, $user2_public);
        $this->_assertDescendant($user2, $user2_private);

        $this->_assertSpan($comm1, 5);

        $this->_assertSpan($comm2, 9);
        $this->_assertSpan($comm2_public, 5);
        $this->_assertSpan($comm2_private, 1);
        $this->_assertSpan($comm2_public_sub0, 1);
        $this->_assertSpan($comm2_public_sub1, 1);
        $this->_assertNoOverlap($comm2_public, $comm2_private);
        $this->_assertNoOverlap($comm2_public_sub0, $comm2_public_sub1);
        $this->_assertDescendant($comm2, $comm2_public);
        $this->_assertDescendant($comm2, $comm2_private);
        $this->_assertDescendant($comm2_public, $comm2_public_sub0);
        $this->_assertDescendant($comm2_public, $comm2_public_sub1);

        // Assert that none of the roots have overlap with each other
        foreach (array($user1, $user2, $comm1, $comm2) as $rootFolder) {
            foreach (array($user1, $user2, $comm1, $comm2) as $otherRoot) {
                if ($rootFolder != $otherRoot) {
                    $this->_assertNoOverlap($rootFolder, $otherRoot);
                }
            }
        }
    }

    /**
     * Test that orphaned items are successfully removed
     */
    public function testRemoveOrphanedItems()
    {
        $notOrphan = $this->Item->load(1001);
        $orphan = $this->Item->load(2001);
        $orphan2 = $this->Item->load(2002);

        $this->assertTrue($notOrphan instanceof ItemDao);
        $this->assertTrue($orphan instanceof ItemDao);
        $this->assertTrue($orphan2 instanceof ItemDao);

        $progress = $this->Progress->createProgress();
        $this->Item->removeOrphans($progress);

        $this->assertEquals($progress->getMaximum(), 2);

        $notOrphan = $this->Item->load(1001);
        $orphan = $this->Item->load(2001);
        $orphan2 = $this->Item->load(2002);

        $this->assertTrue($notOrphan instanceof ItemDao);
        $this->assertEquals($orphan, null);
        $this->assertEquals($orphan2, null);
    }

    /**
     * Test that orphaned itemrevisions are successfully removed
     */
    public function testRemoveOrphanedRevisions()
    {
        $notOrphan = $this->ItemRevision->load(1001);
        $orphan = $this->ItemRevision->load(2001);
        $orphan2 = $this->ItemRevision->load(2002);

        $this->assertTrue($notOrphan instanceof ItemRevisionDao);
        $this->assertTrue($orphan instanceof ItemRevisionDao);
        $this->assertTrue($orphan2 instanceof ItemRevisionDao);

        $progress = $this->Progress->createProgress();
        $this->ItemRevision->removeOrphans($progress);

        $this->assertEquals($progress->getMaximum(), 2);

        $notOrphan = $this->ItemRevision->load(1001);
        $orphan = $this->ItemRevision->load(2001);
        $orphan2 = $this->ItemRevision->load(2002);

        $this->assertTrue($notOrphan instanceof ItemRevisionDao);
        $this->assertEquals($orphan, null);
        $this->assertEquals($orphan2, null);
    }

    /**
     * Test that orphaned bitstreams are successfully removed
     */
    public function testRemoveOrphanedBitstreams()
    {
        $notOrphan = $this->Bitstream->load(1001);
        $notOrphan2 = $this->Bitstream->load(1002);
        $orphan = $this->Bitstream->load(2001);

        $this->assertTrue($notOrphan instanceof BitstreamDao);
        $this->assertTrue($notOrphan2 instanceof BitstreamDao);
        $this->assertTrue($orphan instanceof BitstreamDao);

        $progress = $this->Progress->createProgress();
        $this->Bitstream->removeOrphans($progress);

        $this->assertEquals($progress->getMaximum(), 1);

        $notOrphan = $this->Bitstream->load(1001);
        $notOrphan2 = $this->Bitstream->load(1002);
        $orphan = $this->Bitstream->load(2001);

        $this->assertTrue($notOrphan instanceof BitstreamDao);
        $this->assertTrue($notOrphan2 instanceof BitstreamDao);
        $this->assertEquals($orphan, null);
    }

    /**
     * Assert that a folder has a certain index span
     */
    protected function _assertSpan($folder, $span)
    {
        $this->assertEquals($folder->getRightIndex() - $folder->getLeftIndex(), $span);
    }

    /**
     * Assert that two folders' spans do not overlap at all; that is, that one is not
     * in the subtree of another
     */
    protected function _assertNoOverlap($folder1, $folder2)
    {
        $this->assertTrue($folder1->getLeftIndex() != $folder2->getRightIndex());
        $this->assertTrue($folder2->getLeftIndex() != $folder1->getRightIndex());
        if ($folder1->getLeftIndex() > $folder2->getRightIndex()) {
            $this->assertTrue($folder1->getRightIndex() > $folder2->getLeftIndex());
        } else {
            $this->assertTrue($folder1->getRightIndex() < $folder2->getLeftIndex());
        }
    }

    /**
     * Assert that $descendant is in the subtree of $ancestor
     */
    protected function _assertDescendant($ancestor, $descendant)
    {
        $this->assertTrue($ancestor->getLeftIndex() < $descendant->getLeftIndex());
        $this->assertTrue($ancestor->getRightIndex() > $descendant->getRightIndex());
    }
}
