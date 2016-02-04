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

/** Upgrade the core to version 3.4.2. */
class Upgrade_3_4_2 extends MIDASUpgrade
{
    /** Upgrade a MySQL database. */
    public function mysql()
    {
        $this->db->query('DROP TABLE IF EXISTS `errorlog`;');
        $this->db->query('ALTER TABLE `assetstore` ADD KEY `name` (`name`);');
        $this->db->query('
            ALTER TABLE `bitstream`
                ADD KEY `assetstore_id` (`assetstore_id`),
                ADD KEY `name` (`name`);
        ');
        $this->db->query('
            ALTER TABLE `community`
                ADD KEY `admingroup_id` (`admingroup_id`),
                ADD KEY `folder_id` (`folder_id`),
                ADD KEY `membergroup_id` (`membergroup_id`),
                ADD KEY `moderatorgroup_id` (`moderatorgroup_id`);
        ');
        $this->db->query('
            ALTER TABLE `communityinvitation`
                ADD KEY `community_id` (`community_id`),
                ADD UNIQUE KEY `user_group_id` (`user_id`, `group_id`);
        ');
        $this->db->query('ALTER TABLE `feed` ADD KEY `user_id` (`user_id`);');
        $this->db->query('
            ALTER TABLE `feed2community`
                DROP KEY `feed_community_id`,
                ADD UNIQUE KEY `community_feed_id` (`community_id`,`feed_id`);
        ');
        $this->db->query('
            ALTER TABLE `item`
                ADD KEY `name` (`name`),
                ADD KEY `thumbnail_id` (`thumbnail_id`);
        ');
        $this->db->query('ALTER TABLE `item2folder` ADD KEY `item_id` (`item_id`);');
        $this->db->query('ALTER TABLE `itemrevision` ADD KEY `license_id` (`license_id`);');
        $this->db->query('ALTER TABLE `metadatavalue` ADD KEY `itemrevision_id` (`itemrevision_id`);');
        $this->db->query('
            ALTER TABLE `newuserinvitation`
                ADD KEY `email` (`email`),
                ADD KEY `group_id` (`group_id`),
                ADD KEY `inviter_id` (`inviter_id`);
        ');
        $this->db->query('
            ALTER TABLE `pendinguser`
                ADD KEY `email` (`email`),
                ADD KEY `lastname_firstname` (`lastname`, `firstname`);
        ');
        $this->db->query('ALTER TABLE `setting` ADD UNIQUE KEY `name_module` (`name`, `module`);');
        $this->db->query('ALTER TABLE `token` ADD KEY `userapi_id` (`userapi_id`);');
        $this->db->query('
            ALTER TABLE `user`
                ADD KEY `folder_id` (`folder_id`),
                ADD KEY `lastname_firstname` (`lastname`, `firstname`);
        ');
        $this->db->query('
            ALTER TABLE `user2group`
                DROP KEY `user_group_id`,
                ADD UNIQUE KEY `group_user_id` (`group_id`, `user_id`);
        ');
        $this->db->query('ALTER TABLE `userapi` ADD UNIQUE KEY `user_id_application_name` (`user_id`, `application_name`);');
    }

    /** Upgrade a PostgreSQL database. */
    public function pgsql()
    {
        $this->db->query('DROP TABLE IF EXISTS "errorlog";');
        $this->db->query('ALTER TABLE "communityinvitation" ADD UNIQUE ("user_id", "group_id");');
        $this->db->query('ALTER TABLE "feed2community" ADD UNIQUE ("community_id", "feed_id");');
        $this->db->query('ALTER TABLE "feedpolicygroup" ADD UNIQUE ("feed_id", "group_id");');
        $this->db->query('ALTER TABLE "feedpolicyuser" ADD UNIQUE ("feed_id", "user_id");');
        $this->db->query('ALTER TABLE "itemrevision" ADD UNIQUE ("item_id", "revision");');
        $this->db->query('ALTER TABLE "setting" ADD UNIQUE ("name", "module");');
        $this->db->query('ALTER TABLE "user2group" ADD UNIQUE ("group_id", "user_id");');
        $this->db->query('ALTER TABLE "userapi" ADD UNIQUE ("user_id", "application_name");');
        $this->db->query('CREATE INDEX "assetstore_idx_name" ON "assetstore" ("name");');
        $this->db->query('CREATE INDEX "bitstream_idx_assetstore_id" ON "bitstream" ("assetstore_id");');
        $this->db->query('CREATE INDEX "bitstream_idx_itemrevision_id" ON "bitstream" ("itemrevision_id");');
        $this->db->query('CREATE INDEX "bitstream_idx_name" ON "bitstream" ("name");');
        $this->db->query('CREATE INDEX "community_idx_admingroup_id" ON "community" ("admingroup_id");');
        $this->db->query('CREATE INDEX "community_idx_folder_id" ON "community" ("folder_id");');
        $this->db->query('CREATE INDEX "community_idx_membergroup_id" ON "community" ("membergroup_id");');
        $this->db->query('CREATE INDEX "community_idx_moderatorgroup_id" ON "community" ("moderatorgroup_id");');
        $this->db->query('CREATE INDEX "community_idx_name" ON "community" ("name");');
        $this->db->query('CREATE INDEX "communityinvitation_idx_community_id" ON "communityinvitation" ("community_id");');
        $this->db->query('CREATE INDEX "feed_idx_user_id" ON "feed" ("user_id");');
        $this->db->query('CREATE INDEX "group_idx_community_id" ON "group" ("community_id");');
        $this->db->query('CREATE INDEX "item_idx_name" ON "item" ("name");');
        $this->db->query('CREATE INDEX "item_idx_thumbnail_id" ON "item" ("thumbnail_id");');
        $this->db->query('CREATE INDEX "item2folder_idx_item_id" ON "item2folder" ("item_id");');
        $this->db->query('CREATE INDEX "itemrevision_idx_date" ON "itemrevision" ("date");');
        $this->db->query('CREATE INDEX "itemrevision_idx_license_id" ON "itemrevision" ("license_id");');
        $this->db->query('CREATE INDEX "itemrevision_idx_user_id" ON "itemrevision" ("user_id");');
        $this->db->query('CREATE INDEX "metadata_idx_metadatatype" ON "metadata" ("metadatatype");');
        $this->db->query('CREATE INDEX "metadatavalue_idx_itemrevision_id" ON "metadatavalue" ("itemrevision_id");');
        $this->db->query('CREATE INDEX "newuserinvitation_idx_email" ON "newuserinvitation" ("email");');
        $this->db->query('CREATE INDEX "newuserinvitation_idx_group_id" ON "newuserinvitation" ("group_id");');
        $this->db->query('CREATE INDEX "newuserinvitation_idx_inviter_id" ON "newuserinvitation" ("inviter_id");');
        $this->db->query('CREATE INDEX "pendinguser_idx_email" ON "pendinguser" ("email");');
        $this->db->query('CREATE INDEX "pendinguser_idx_lastname_firstname" ON "pendinguser" ("lastname", "firstname");');
        $this->db->query('CREATE INDEX "token_idx_userapi_id" ON "token" ("userapi_id");');
        $this->db->query('CREATE INDEX "user_idx_email" ON "user" ("email");');
        $this->db->query('CREATE INDEX "user_idx_folder_id" ON "user" ("folder_id");');
        $this->db->query('CREATE INDEX "user_idx_lastname_firstname" ON "user" ("lastname", "firstname");');
    }

    /** Upgrade a SQLite database. */
    public function sqlite()
    {
        $this->db->query('DROP TABLE IF EXISTS "errorlog";');
        $this->db->query('CREATE INDEX IF NOT EXISTS "assetstore_name_idx" ON "assetstore" ("name");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "bitstream_assetstore_id_idx" ON "bitstream" ("assetstore_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "bitstream_itemrevision_id_idx" ON "bitstream" ("itemrevision_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "bitstream_name_idx" ON "bitstream" ("name");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "community_admingroup_id_idx" ON "community" ("admingroup_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "community_folder_id_idx" ON "community" ("folder_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "community_membergroup_id_idx" ON "community" ("membergroup_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "community_moderatorgroup_id_idx" ON "community" ("moderatorgroup_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "community_name_idx" ON "community" ("name");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "communityinvitation_community_id_idx" ON "communityinvitation" ("community_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "feed_user_id_idx" ON "feed" ("user_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "group_community_id_idx" ON "group" ("community_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "item_name_idx" ON "item" ("name");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "item_thumbnail_id_idx" ON "item" ("thumbnail_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "item2folder_item_id_idx" ON "item2folder" ("item_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "itemrevision_date_idx" ON "itemrevision" ("date");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "itemrevision_license_id_idx" ON "itemrevision" ("license_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "itemrevision_user_id_idx" ON "itemrevision" ("user_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "metadata_metadatatype_idx" ON "metadata" ("metadatatype");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "metadatavalue_itemrevision_id_idx" ON "metadatavalue" ("itemrevision_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "newuserinvitation_email_idx" ON "newuserinvitation" ("email");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "newuserinvitation_group_id_idx" ON "newuserinvitation" ("group_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "newuserinvitation_inviter_id_idx" ON "newuserinvitation" ("inviter_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "pendinguser_email_idx" ON "pendinguser" ("email");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "pendinguser_lastname_firstname_idx" ON "pendinguser" ("lastname", "firstname");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "token_userapi_id_idx" ON "token" ("userapi_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "user_email_idx" ON "user" ("email");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "user_folder_id_idx" ON "user" ("folder_id");');
        $this->db->query('CREATE INDEX IF NOT EXISTS "user_lastname_firstname_idx" ON "user" ("lastname", "firstname");');
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS "communityinvitation_user_group_id_idx" ON "communityinvitation" ("user_id", "group_id");');
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS "feed2community_community_feed_id_idx" ON "feed2community" ("community_id", "feed_id");');
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS "feedpolicygroup_feed_group_id_idx" ON "feedpolicygroup" ("feed_id", "group_id");');
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS "feedpolicyuser_feed_user_id_idx" ON "feedpolicyuser" ("feed_id", "user_id");');
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS "itemrevision_item_revision_idx" ON "itemrevision" ("item_id", "revision");');
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS "setting_name_module_idx" ON "setting" ("name", "module");');
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS "user2group_group_user_id_idx" ON "user2group" ("group_id", "user_id");');
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS "userapi_user_id_application_name_idx" ON "userapi" ("user_id", "application_name");');
    }
}
