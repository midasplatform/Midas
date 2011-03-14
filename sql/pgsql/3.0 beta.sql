SET client_encoding = 'UTF8';
--- Only supported on PostGreSQL 8.3 and higher
--- SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
--- SET escape_string_warning = off;

SET search_path = public, pg_catalog;
--- SET default_tablespace = '';
--- SET default_with_oids = false;

-- --------------------------------------------------------

--
-- Table structure for table assetstore
--

CREATE TABLE  assetstore (
  assetstore_id serial  PRIMARY KEY,
  name  character varying(256) NOT NULL,
  path character varying(512) NOT NULL,
  type smallint NOT NULL
)  ;


-- --------------------------------------------------------

--
-- Table structure for table task
--

CREATE TABLE  task (
  task_id serial  PRIMARY KEY,
  type smallint NOT NULL,
  resource_type smallint NOT NULL,
  resource_id bigint NOT NULL,
  parameters character varying(512) NOT NULL
) ;


-- --------------------------------------------------------

--
-- Table structure for table bitstream
--

CREATE TABLE  bitstream (
  bitstream_id serial  PRIMARY KEY,
  itemrevision_id bigint NOT NULL,
  name  character varying(256) NOT NULL,
  mimetype character varying(30) NOT NULL,
  sizebytes bigint NOT NULL,
  checksum character varying(64) NOT NULL,
  path character varying(512) NOT NULL,
  assetstore_id integer NOT NULL,
  date timestamp without time zone NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table community
--

CREATE TABLE  community (
  community_id serial  PRIMARY KEY,
  name  character varying(256) NOT NULL,
  description text NOT NULL,
  creation timestamp without time zone NULL DEFAULT NULL ,
  privacy integer NOT NULL,
  folder_id bigint NOT NULL,
  publicfolder_id bigint NOT NULL,
  privatefolder_id bigint NOT NULL,
  admingroup_id bigint NOT NULL,
  moderatorgroup_id bigint NOT NULL,
  membergroup_id bigint NOT NULL

)  ;

-- --------------------------------------------------------

--
-- Table structure for table folder
--

CREATE TABLE  folder (
  folder_id  serial  PRIMARY KEY,
  left_indice bigint NOT NULL, 
  right_indice bigint NOT NULL,  
  parent_id bigint NOT NULL DEFAULT '0',
  name  character varying(256) NOT NULL,
  description text NOT NULL,
  date timestamp without time zone NOT NULL
)  ;

-- --------------------------------------------------------

--
-- Table structure for table folderpolicygroup
--

CREATE TABLE  folderpolicygroup (
  folder_id bigint NOT NULL,
  group_id bigint NOT NULL,
  policy smallint NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table folderpolicyuser
--

CREATE TABLE  folderpolicyuser (
  folder_id bigint NOT NULL,
  user_id bigint NOT NULL,
  policy smallint NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table group
--

CREATE TABLE  "group" (
  group_id serial  PRIMARY KEY,
  community_id bigint NOT NULL,
  name  character varying(256) NOT NULL
)  ;

INSERT INTO "group" (group_id,community_id,name) VALUES (0,0,'Anonymous');


-- --------------------------------------------------------

--
-- Table structure for table item
--

CREATE TABLE  item (
  item_id serial  PRIMARY KEY,
  name character varying(250) NOT NULL,
  date timestamp without time zone NOT NULL,
  description character varying(20) NOT NULL,
  type integer NOT NULL,
  thumbnail  character varying(256) NOT NULL,
  sizebytes bigint NOT NULL DEFAULT  '0'
) ;

-- --------------------------------------------------------

--
-- Table structure for table itempolicygroup
--

CREATE TABLE  itempolicygroup (
  item_id bigint NOT NULL,
  group_id bigint NOT NULL,
  policy smallint NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table itempolicyuser
--

CREATE TABLE  itempolicyuser (
  item_id bigint NOT NULL,
  user_id bigint NOT NULL,
  policy smallint NOT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table item2folder
--

CREATE TABLE  item2folder (
  item_id bigint NOT NULL,
  folder_id bigint NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table item2keyword
--

CREATE TABLE  item2keyword (
  item_id bigint NOT NULL,
  keyword_id bigint NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table itemrevision
--

CREATE TABLE  itemrevision (
  itemrevision_id serial  PRIMARY KEY,
  item_id bigint NOT NULL,
  revision integer NOT NULL,
  date timestamp without time zone NOT NULL,
  changes text NOT NULL,
  user_id integer NOT NULL
)  ;

-- --------------------------------------------------------

--
-- Table structure for table itemkeyword
--

CREATE TABLE  itemkeyword (
  keyword_id serial  PRIMARY KEY,
  value  character varying(256) NOT NULL,
  relevance integer NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table metadata
--

CREATE TABLE  metadata (
  metadata_id serial  PRIMARY KEY,
  metadatatype_id integer NOT NULL,
  element  character varying(256) NOT NULL,
  qualifier  character varying(256) NOT NULL,
  description character varying(512) NOT NULL
)  ;

-- --------------------------------------------------------

--
-- Table structure for table metadatadocumentvalue
--

CREATE TABLE  metadatadocumentvalue (
  metadata_id bigint NOT NULL,
  itemrevision_id bigint NOT NULL,
  value character varying(1024) NOT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table metadatatype
--

CREATE TABLE  metadatatype (
  metadatatype_id serial  PRIMARY KEY,
  name  character varying(256) NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table metadatavalue
--

CREATE TABLE  metadatavalue (
  metadata_id bigint NOT NULL,
  itemrevision_id bigint NOT NULL,
  value character varying(1024) NOT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table user
--

CREATE TABLE  "user" (
  user_id serial  PRIMARY KEY,
  password character varying(100) NOT NULL,
  firstname  character varying(256) NOT NULL,
  company  character varying(256) ,
  thumbnail  character varying(256) ,
  lastname  character varying(256) NOT NULL,
  email  character varying(256) NOT NULL,
  privacy integer NOT NULL DEFAULT 0,
  admin integer NOT NULL DEFAULT 0,
  folder_id bigint NOT NULL,
  creation timestamp without time zone NULL DEFAULT NULL ,
  publicfolder_id bigint NOT NULL,
  privatefolder_id bigint NOT NULL
)  ;


-- --------------------------------------------------------

--
-- Table structure for table user2group
--

CREATE TABLE  user2group (
  user_id bigint NOT NULL,
  group_id bigint NOT NULL
);



-- --------------------------------------------------------

--
-- Table structure for table newsfeed
--

CREATE TABLE  feed (
  feed_id serial  PRIMARY KEY,
  date timestamp without time zone NOT NULL,
  user_id bigint NOT NULL,
  type integer NOT NULL,
  ressource  character varying(256) NOT NULL
) ;


-- --------------------------------------------------------

--
-- Table structure for table feed2community
--

CREATE TABLE  feed2community (
  feed_id bigint NOT NULL,
  community_id bigint NOT NULL
) ;


-- --------------------------------------------------------

--
-- Table structure for table feedpolicygroup
--

CREATE TABLE  feedpolicygroup (
  feed_id bigint NOT NULL,
  group_id bigint NOT NULL,
  policy smallint NOT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table feedpolicyuser
--

CREATE TABLE  feedpolicyuser (
  feed_id bigint NOT NULL,
  user_id bigint NOT NULL,
  policy smallint NOT NULL
) ;