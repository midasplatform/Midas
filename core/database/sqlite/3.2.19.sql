-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- SQLite core database, version 3.2.19

CREATE TABLE IF NOT EXISTS "activedownload" (
    "activedownload_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "ip" TEXT NOT NULL DEFAULT '',
    "date_creation" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "last_update" TEXT NOT NULL DEFAULT '0000-00-00 00:00:00'
);

CREATE INDEX IF NOT EXISTS "activedownload_ip_idx" ON "activedownload" ("ip");

CREATE TABLE IF NOT EXISTS "assetstore" (
    "assetstore_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "name" TEXT NOT NULL,
    "path" TEXT NOT NULL,
    "type" INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS "bitstream" (
    "bitstream_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "itemrevision_id" INTEGER NOT NULL,
    "name" TEXT NOT NULL,
    "mimetype" TEXT NOT NULL,
    "sizebytes" INTEGER NOT NULL,
    "checksum" TEXT NOT NULL,
    "path" TEXT NOT NULL,
    "assetstore_id" INTEGER NOT NULL,
    "date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "bitstream_checksum_idx" ON "bitstream" ("checksum");

CREATE TABLE IF NOT EXISTS "community" (
    "community_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "name" TEXT NOT NULL,
    "description" TEXT NOT NULL,
    "creation" TEXT,
    "privacy" INTEGER NOT NULL,
    "folder_id" INTEGER,
    "admingroup_id" INTEGER,
    "moderatorgroup_id" INTEGER,
    "view" INTEGER NOT NULL DEFAULT 0,
    "membergroup_id" INTEGER,
    "can_join" INTEGER NOT NULL DEFAULT 0,
    "uuid" TEXT DEFAULT ''
);

CREATE TABLE IF NOT EXISTS "communityinvitation" (
    "communityinvitation_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "community_id" INTEGER,
    "user_id" INTEGER,
    "group_id" INTEGER
);

CREATE TABLE IF NOT EXISTS "errorlog" (
    "errorlog_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "priority" INTEGER NOT NULL,
    "module" TEXT NOT NULL,
    "message" TEXT NOT NULL,
    "datetime" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "feed" (
    "feed_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "user_id" INTEGER NOT NULL,
    "type" INTEGER NOT NULL,
    "resource" TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS "feed2community" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "feed_id" INTEGER NOT NULL,
    "community_id" INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS "feedpolicygroup" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "feed_id" INTEGER NOT NULL,
    "group_id" INTEGER NOT NULL,
    "policy" INTEGER NOT NULL,
    "date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "feedpolicyuser" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "feed_id" INTEGER NOT NULL,
    "user_id" INTEGER NOT NULL,
    "policy" INTEGER NOT NULL,
    "date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "folder" (
    "folder_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "left_index" INTEGER NOT NULL,
    "right_index" INTEGER NOT NULL,
    "parent_id" INTEGER NOT NULL DEFAULT 0,
    "name" TEXT NOT NULL,
    "description" TEXT NOT NULL,
    "view" INTEGER NOT NULL DEFAULT 0,
    "date_update" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "teaser" TEXT DEFAULT '',
    "privacy_status" INTEGER NOT NULL DEFAULT 0,
    "uuid" TEXT DEFAULT '',
    "date_creation" TEXT NOT NULL DEFAULT '0000-00-00 00:00:00'
);

CREATE INDEX IF NOT EXISTS "folder_left_index_idx" ON "folder" ("left_index");
CREATE INDEX IF NOT EXISTS "folder_parent_id_idx" ON "folder" ("parent_id");
CREATE INDEX IF NOT EXISTS "folder_right_index_idx" ON "folder" ("right_index");

CREATE TABLE IF NOT EXISTS "folderpolicygroup" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "folder_id" INTEGER NOT NULL,
    "group_id" INTEGER NOT NULL,
    "policy" INTEGER NOT NULL,
    "date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE ("folder_id", "group_id")
);

CREATE TABLE IF NOT EXISTS "folderpolicyuser" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "folder_id" INTEGER NOT NULL,
    "user_id" INTEGER NOT NULL,
    "policy" INTEGER NOT NULL,
    "date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE ("folder_id", "user_id")
);

CREATE TABLE IF NOT EXISTS "group" (
    "group_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "community_id" INTEGER NOT NULL,
    "name" TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS "item" (
    "item_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "name" TEXT NOT NULL,
    "date_update" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "description" TEXT NOT NULL,
    "type" INTEGER NOT NULL,
    "view" INTEGER NOT NULL DEFAULT 0,
    "download" INTEGER NOT NULL DEFAULT 0,
    "sizebytes" INTEGER NOT NULL DEFAULT 0,
    "privacy_status" INTEGER NOT NULL DEFAULT 0,
    "uuid" TEXT DEFAULT '',
    "date_creation" TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
    "thumbnail_id" INTEGER
);

CREATE TABLE IF NOT EXISTS "item2folder" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "item_id" INTEGER NOT NULL,
    "folder_id" INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS "item2folder_folder_id_idx" ON "item2folder" ("folder_id");

CREATE TABLE IF NOT EXISTS "itempolicygroup" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "item_id" INTEGER NOT NULL,
    "group_id" INTEGER NOT NULL,
    "policy" INTEGER NOT NULL,
    "date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE ("item_id", "group_id")
);

CREATE TABLE IF NOT EXISTS "itempolicyuser" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "item_id" INTEGER NOT NULL,
    "user_id" INTEGER NOT NULL,
    "policy" INTEGER NOT NULL,
    "date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE ("item_id", "user_id")
);

CREATE TABLE IF NOT EXISTS "itemrevision" (
    "itemrevision_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "item_id" INTEGER NOT NULL,
    "revision" INTEGER NOT NULL,
    "date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "changes" TEXT NOT NULL,
    "user_id" INTEGER NOT NULL,
    "uuid" TEXT DEFAULT '',
    "license_id" INTEGER
);

CREATE TABLE IF NOT EXISTS "license" (
    "license_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "name" TEXT NOT NULL,
    "fulltext" TEXT NOT NULL
);

INSERT OR IGNORE INTO "license" VALUES (1, 'Public (PDDL)', '**You are free:**\n\n* To Share: To copy, distribute and use the database.\n* To Create: To produce works from the database.\n* To Adapt: To modify, transform, and build upon the database.\n\n[Full License Information](http://opendatacommons.org/licenses/pddl/summary)');
INSERT OR IGNORE INTO "license" VALUES (2, 'Public: Attribution (ODC-BY)', '**You are free:**\n\n* To Share: To copy, distribute and use the database.\n* To Create: To produce works from the database.\n* To Adapt: To modify, transform, and build upon the database.\n\n**As long as you:**\n\n* Attribute: You must attribute any public use of the database, or works produced from the database, in the manner specified in the license. For any use or redistribution of the database, or works produced from it, you must make clear to others the license of the database and keep intact any notices on the original database.\n\n[Full License Information](http://opendatacommons.org/licenses/by/summary)');
INSERT OR IGNORE INTO "license" VALUES (3, 'Public: Attribution, Share-Alike (ODbL)', '**You are free:**\n\n* To Share: To copy, distribute and use the database.\n* To Create: To produce works from the database.\n* To Adapt: To modify, transform, and build upon the database.\n\n**As long as you:**\n\n* Attribute: You must attribute any public use of the database, or works produced from the database, in the manner specified in the license. For any use or redistribution of the database, or works produced from it, you must make clear to others the license of the database and keep intact any notices on the original database.\n* Share-Alike: If you publicly use any adapted version of this database, or works produced from an adapted database, you must also offer that adapted database under the ODbL.\n* Keep open: If you redistribute the database, or an adapted version of it, then you may use technological measures that restrict the work (such as DRM) as long as you also redistribute a version without such measures.\n\n[Full License Information](http://opendatacommons.org/licenses/odbl/summary)');
INSERT OR IGNORE INTO "license" VALUES (4, 'Private: All Rights Reserved', 'This work is copyrighted by its author or licensor. You must not share, distribute, or modify this work without the prior consent of the author or licensor.');
INSERT OR IGNORE INTO "license" VALUES (5, 'Public: Attribution (CC BY 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n* To make commercial use of the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n\n[Full License Information](http://creativecommons.org/licenses/by/3.0/)');
INSERT OR IGNORE INTO "license" VALUES (6, 'Public: Attribution, Share-Alike (CC BY-SA 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n* To make commercial use of the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Share-Alike: If you alter, transform, or build upon this work, you may distribute the resulting work only under the same or similar license to this one.\n\n[Full License Information](http://creativecommons.org/licenses/by-sa/3.0/)');
INSERT OR IGNORE INTO "license" VALUES (7, 'Public: Attribution, No Derivative Works (CC BY-ND 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To make commercial use of the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* No Derivative Works: You may not alter, transform, or build upon this work.\n\n[Full License Information](http://creativecommons.org/licenses/by-nd/3.0/)');
INSERT OR IGNORE INTO "license" VALUES (8, 'Public: Attribution, Non-Commercial (CC BY-NC 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Non-Commercial: You may not use this work for commercial purposes.\n\n[Full License Information](http://creativecommons.org/licenses/by-nc/3.0/)');
INSERT OR IGNORE INTO "license" VALUES (9, 'Public: Attribution, Non-Commercial, Share-Alike (CC BY-NC-SA 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n* To Remix: To adapt the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Non-Commercial: You may not use this work for commercial purposes.\n* Share-Alike: If you alter, transform, or build upon this work, you may distribute the resulting work only under the same or similar license to this one.\n\n[Full License Information](http://creativecommons.org/licenses/by-nc-sa/3.0/)');
INSERT OR IGNORE INTO "license" VALUES (10, 'Public: Attribution, Non-Commercial, No Derivative Works (CC BY-NC-ND 3.0)', '**You are free:**\n\n* To Share: To copy, distribute and transmit the work.\n\n**Under the following conditions:**\n\n* Attribution: You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).\n* Non-Commercial: You may not use this work for commercial purposes.\n* No Derivative Works: You may not alter, transform, or build upon this work.\n\n[Full License Information](http://creativecommons.org/licenses/by-nc-nd/3.0/)');

CREATE TABLE IF NOT EXISTS "metadata" (
    "metadata_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "metadatatype" INTEGER NOT NULL DEFAULT 0,
    "element" TEXT NOT NULL,
    "qualifier" TEXT NOT NULL
);

INSERT OR IGNORE INTO "metadata" VALUES (1, 0, 'contributor', 'author');
INSERT OR IGNORE INTO "metadata" VALUES (2, 0, 'date', 'uploaded');
INSERT OR IGNORE INTO "metadata" VALUES (3, 0, 'date', 'issued');
INSERT OR IGNORE INTO "metadata" VALUES (4, 0, 'date', 'created');
INSERT OR IGNORE INTO "metadata" VALUES (5, 0, 'identifier', 'citation');
INSERT OR IGNORE INTO "metadata" VALUES (6, 0, 'identifier', 'uri');
INSERT OR IGNORE INTO "metadata" VALUES (7, 0, 'identifier', 'pubmed');
INSERT OR IGNORE INTO "metadata" VALUES (8, 0, 'identifier', 'doi');
INSERT OR IGNORE INTO "metadata" VALUES (9, 0, 'description', 'general');
INSERT OR IGNORE INTO "metadata" VALUES (10, 0, 'description', 'provenance');
INSERT OR IGNORE INTO "metadata" VALUES (11, 0, 'description', 'sponsorship');
INSERT OR IGNORE INTO "metadata" VALUES (12, 0, 'description', 'publisher');
INSERT OR IGNORE INTO "metadata" VALUES (13, 0, 'subject', 'keyword');
INSERT OR IGNORE INTO "metadata" VALUES (14, 0, 'subject', 'ocis');

CREATE TABLE IF NOT EXISTS "metadatavalue" (
    "metadatavalue_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "metadata_id" INTEGER NOT NULL,
    "itemrevision_id" INTEGER NOT NULL,
    "value" TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS "newuserinvitation" (
    "newuserinvitation_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "auth_key" TEXT NOT NULL,
    "email" TEXT NOT NULL,
    "inviter_id" INTEGER NOT NULL,
    "community_id" INTEGER NOT NULL,
    "group_id" INTEGER NOT NULL,
    "date_creation" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "password" (
    "hash" TEXT PRIMARY KEY NOT NULL
);

CREATE TABLE IF NOT EXISTS "pendinguser" (
    "pendinguser_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "auth_key" TEXT NOT NULL,
    "email" TEXT NOT NULL,
    "firstname" TEXT NOT NULL,
    "lastname" TEXT NOT NULL,
    "date_creation" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "salt" TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS "progress" (
    "progress_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "message" TEXT NOT NULL,
    "current" INTEGER NOT NULL,
    "maximum" INTEGER NOT NULL,
    "date_creation" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "last_update" TEXT NOT NULL DEFAULT '0000-00-00 00:00:00'
);

CREATE TABLE IF NOT EXISTS "setting" (
    "setting_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "module" TEXT NOT NULL,
    "name" TEXT NOT NULL,
    "value" TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS "token" (
    "token_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "userapi_id" INTEGER NOT NULL,
    "token" TEXT NOT NULL,
    "expiration_date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "user" (
    "user_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "firstname" TEXT NOT NULL,
    "company" TEXT,
    "thumbnail" TEXT,
    "lastname" TEXT NOT NULL,
    "email" TEXT NOT NULL,
    "privacy" INTEGER NOT NULL DEFAULT 0,
    "admin" INTEGER NOT NULL DEFAULT 0,
    "view" INTEGER NOT NULL DEFAULT 0,
    "folder_id" INTEGER,
    "creation" TEXT,
    "uuid" TEXT DEFAULT '',
    "city" TEXT DEFAULT '',
    "country" TEXT DEFAULT '',
    "website" TEXT DEFAULT '',
    "biography" TEXT DEFAULT '',
    "dynamichelp" INTEGER DEFAULT 1,
    "hash_alg" TEXT NOT NULL DEFAULT '',
    "salt" TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS "user2group" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "user_id" INTEGER NOT NULL,
    "group_id" INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS "userapi" (
    "userapi_id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "user_id" INTEGER NOT NULL,
    "apikey" TEXT NOT NULL,
    "application_name" TEXT NOT NULL,
    "token_expiration_time" INTEGER NOT NULL,
    "creation_date" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
