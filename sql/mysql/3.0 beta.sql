-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 19, 2011 at 10:32 AM
-- Server version: 5.1.41
-- PHP Version: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `midas3`
--

-- --------------------------------------------------------

--
-- Table structure for table `assetstore`
--

CREATE TABLE IF NOT EXISTS `assetstore` (
  `assetstore_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `path` varchar(512) NOT NULL,
  `type` tinyint(4) NOT NULL,
  PRIMARY KEY (`assetstore_id`),
  UNIQUE KEY `path` (`path`,`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;


-- --------------------------------------------------------

--
-- Table structure for table `task`
--

CREATE TABLE IF NOT EXISTS `task` (
  `task_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) NOT NULL,
  `resource_type` tinyint(4) NOT NULL,
  `resource_id` bigint(20) NOT NULL,
  `parameters` varchar(512) NOT NULL,
  PRIMARY KEY (`task_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;


-- --------------------------------------------------------

--
-- Table structure for table `bitstream`
--

CREATE TABLE IF NOT EXISTS `bitstream` (
  `bitstream_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `itemrevision_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mimetype` varchar(30) NOT NULL,
  `sizebytes` bigint(20) NOT NULL,
  `checksum` varchar(64) NOT NULL,
  `path` varchar(512) NOT NULL,
  `assetstore_id` int(11) NOT NULL,
  `date` timestamp NULL DEFAULT NULL ,
  PRIMARY KEY (`bitstream_id`),
  KEY `itemrevision_id` (`itemrevision_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=680 ;

-- --------------------------------------------------------

--
-- Table structure for table `community`
--

CREATE TABLE IF NOT EXISTS `community` (
  `community_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `creation` timestamp NULL DEFAULT NULL ,
  `privacy` tinyint NOT NULL,
  `folder_id` bigint(20) NOT NULL,
  `publicfolder_id` bigint(20) NOT NULL,
  `privatefolder_id` bigint(20) NOT NULL,
  `admingroup_id` bigint(20) NOT NULL,
  `moderatorgroup_id` bigint(20) NOT NULL,
  `membergroup_id` bigint(20) NOT NULL,
  PRIMARY KEY (`community_id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `folder`
--

CREATE TABLE IF NOT EXISTS `folder` (
  `folder_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `left_indice` bigint(20) NOT NULL, 
  `right_indice` bigint(20) NOT NULL,  
  `parent_id` bigint(20) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `date` timestamp NULL DEFAULT NULL ,
  PRIMARY KEY (`folder_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Describes a directory' AUTO_INCREMENT=23 ;

-- --------------------------------------------------------

--
-- Table structure for table `folderpolicygroup`
--

CREATE TABLE IF NOT EXISTS `folderpolicygroup` (
  `folder_id` bigint(20) NOT NULL,
  `group_id` bigint(20) NOT NULL,
  `policy` tinyint(4) NOT NULL,
  UNIQUE KEY `folder_id` (`folder_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `folderpolicyuser`
--

CREATE TABLE IF NOT EXISTS `folderpolicyuser` (
  `folder_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `policy` tinyint(4) NOT NULL,
  UNIQUE KEY `folder_id` (`folder_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `group`
--

CREATE TABLE IF NOT EXISTS `group` (
  `group_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `community_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `community_id` (`community_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

INSERT INTO `group` (group_id,community_id,name) VALUES (0,0,'Anonymous');
-- --------------------------------------------------------

--
-- Table structure for table `item`
--

CREATE TABLE IF NOT EXISTS `item` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `date` timestamp NULL DEFAULT NULL ,
  `description` varchar(20) NOT NULL,
  `type` int(11) NOT NULL,
  `thumbnail` varchar(255) NOT NULL,
  `sizebytes` BIGINT( 20 ) NOT NULL DEFAULT  '0',
  PRIMARY KEY (`item_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=37 ;

-- --------------------------------------------------------

--
-- Table structure for table `itempolicygroup`
--

CREATE TABLE IF NOT EXISTS `itempolicygroup` (
  `item_id` bigint(20) NOT NULL,
  `group_id` bigint(20) NOT NULL,
  `policy` tinyint(4) NOT NULL,
  UNIQUE KEY `item_id` (`item_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `itempolicyuser`
--

CREATE TABLE IF NOT EXISTS `itempolicyuser` (
  `item_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `policy` tinyint(4) NOT NULL,
  UNIQUE KEY `item_id` (`item_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `item2folder`
--

CREATE TABLE IF NOT EXISTS `item2folder` (
  `item_id` bigint(20) NOT NULL,
  `folder_id` bigint(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `item2keyword`
--

CREATE TABLE IF NOT EXISTS `item2keyword` (
  `item_id` bigint(20) NOT NULL,
  `keyword_id` bigint(20) NOT NULL,
  UNIQUE KEY `item_id` (`item_id`,`keyword_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `itemrevision`
--

CREATE TABLE IF NOT EXISTS `itemrevision` (
  `itemrevision_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) NOT NULL,
  `revision` int(11) NOT NULL,
  `date` timestamp NULL DEFAULT NULL ,
  `changes` text NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`itemrevision_id`),
  UNIQUE KEY `item_id` (`item_id`,`revision`),
  KEY `user_id` (`user_id`),
  KEY `date` (`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=685 ;

-- --------------------------------------------------------

--
-- Table structure for table `itemkeyword`
--

CREATE TABLE IF NOT EXISTS `itemkeyword` (
  `keyword_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  `relevance` int(11) NOT NULL,
  PRIMARY KEY (`keyword_id`),
  UNIQUE KEY `value` (`value`),
  KEY `relevance` (`relevance`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `metadata`
--

CREATE TABLE IF NOT EXISTS `metadata` (
  `metadata_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `metadatatype_id` int(11) NOT NULL,
  `element` varchar(255) NOT NULL,
  `qualifier` varchar(255) NOT NULL,
  `description` varchar(512) NOT NULL,
  PRIMARY KEY (`metadata_id`),
  KEY `metadatatype_id` (`metadatatype_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `metadatadocumentvalue`
--

CREATE TABLE IF NOT EXISTS `metadatadocumentvalue` (
  `metadata_id` bigint(20) NOT NULL,
  `itemrevision_id` bigint(20) NOT NULL,
  `value` varchar(1024) NOT NULL,
  KEY `metadata_id` (`metadata_id`),
  KEY `itemrevision_id` (`itemrevision_id`),
  KEY `value` (`value`(1000))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `metadatatype`
--

CREATE TABLE IF NOT EXISTS `metadatatype` (
  `metadatatype_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`metadatatype_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `metadatavalue`
--

CREATE TABLE IF NOT EXISTS `metadatavalue` (
  `metadata_id` bigint(20) NOT NULL,
  `itemrevision_id` bigint(20) NOT NULL,
  `value` varchar(1024) NOT NULL,
  KEY `metadata_id` (`metadata_id`,`itemrevision_id`),
  KEY `value` (`value`(1000))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `password` varchar(100) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `company` varchar(255) ,
  `thumbnail` varchar(255) ,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `privacy` tinyint NOT NULL DEFAULT 0,
  `admin` tinyint NOT NULL DEFAULT 0,
  `folder_id` bigint(20) NOT NULL,
  `creation` timestamp NULL DEFAULT NULL ,
  `publicfolder_id` bigint(20) NOT NULL,
  `privatefolder_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Table structure for table `user2group`
--

CREATE TABLE IF NOT EXISTS `user2group` (
  `user_id` bigint(20) NOT NULL,
  `group_id` bigint(20) NOT NULL,
  UNIQUE KEY `user_id` (`user_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Table structure for table `newsfeed`
--

CREATE TABLE IF NOT EXISTS `feed` (
  `feed_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `date` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) NOT NULL,
  `type` int NOT NULL,
  `ressource` varchar(255) NOT NULL,
  PRIMARY KEY (`feed_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `feed2community`
--

CREATE TABLE IF NOT EXISTS `feed2community` (
  `feed_id` bigint(20) NOT NULL,
  `community_id` bigint(20) NOT NULL,
  UNIQUE KEY `feed_id` (`feed_id`,`community_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `feedpolicygroup`
--

CREATE TABLE IF NOT EXISTS `feedpolicygroup` (
  `feed_id` bigint(20) NOT NULL,
  `group_id` bigint(20) NOT NULL,
  `policy` tinyint(4) NOT NULL,
  UNIQUE KEY `feed_id` (`feed_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `feedpolicyuser`
--

CREATE TABLE IF NOT EXISTS `feedpolicyuser` (
  `feed_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `policy` tinyint(4) NOT NULL,
  UNIQUE KEY `feed_id` (`feed_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;