-- MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the thumbnailcreator module, version 1.0.3

CREATE TABLE IF NOT EXISTS `thumbnailcreator_itemthumbnail` (
    `itemthumbnail_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `item_id` bigint(20),
    `thumbnail_id` bigint(20),
    PRIMARY KEY (`itemthumbnail_id`),
    KEY (`item_id`)
) DEFAULT CHARSET=utf8;

INSERT INTO `setting` (`module`, `name`, `value`) VALUES
('thumbnailcreator', 'provider', 'gd'),
('thumbnailcreator', 'format', 'jpg'),
('thumbnailcreator', 'image_magick', ''),
('thumbnailcreator', 'use_thumbnailer', '0'),
('thumbnailcreator', 'thumbnailer', '');
