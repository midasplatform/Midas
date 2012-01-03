--
-- PostgreSQL database dump
--

-- Dumped from database version 9.0.3
-- Dumped by pg_dump version 9.0.3
-- Started on 2011-06-17 12:09:46

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;


--
-- TOC entry 1586 (class 1259 OID 43030)
-- Dependencies: 6
-- Name: assetstore; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE assetstore (
    assetstore_id serial PRIMARY KEY,
    name character varying(256) NOT NULL,
    path character varying(512) NOT NULL,
    type smallint NOT NULL
);



--
-- TOC entry 1588 (class 1259 OID 43041)
-- Dependencies: 6
-- Name: bitstream; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE bitstream (
    bitstream_id serial PRIMARY KEY,
    itemrevision_id bigint NOT NULL,
    name character varying(256) NOT NULL,
    mimetype character varying(30) NOT NULL,
    sizebytes bigint NOT NULL,
    checksum character varying(64) NOT NULL,
    path character varying(512) NOT NULL,
    assetstore_id integer NOT NULL,
    date timestamp without time zone NOT NULL
);



-- TOC entry 1590 (class 1259 OID 43052)
-- Dependencies: 1917 1918 1919 6
-- Name: community; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE community (
    community_id serial PRIMARY KEY,
    name character varying(256) NOT NULL,
    description text NOT NULL,
    creation timestamp without time zone,
    privacy integer NOT NULL,
    folder_id bigint,
    publicfolder_id bigint,
    privatefolder_id bigint,
    admingroup_id bigint,
    moderatorgroup_id bigint,
    view bigint DEFAULT 0::bigint NOT NULL,
    membergroup_id bigint,
    can_join integer DEFAULT 0 NOT NULL,
    uuid character varying(512) DEFAULT ''::character varying
);



--
-- TOC entry 1634 (class 1259 OID 43336)
-- Dependencies: 6
-- Name: communityinvitation; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE communityinvitation (
    communityinvitation_id serial PRIMARY KEY,
    community_id bigint,
    user_id bigint
);


--
-- TOC entry 1592 (class 1259 OID 43064)
-- Dependencies: 6
-- Name: errorlog; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE errorlog (
    errorlog_id_id serial PRIMARY KEY,
    priority integer NOT NULL,
    module character varying(256) NOT NULL,
    message text NOT NULL,
    datetime timestamp without time zone
);



--
-- TOC entry 1626 (class 1259 OID 43246)
-- Dependencies: 6
-- Name: feed; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE feed (
    feed_id serial PRIMARY KEY,
    date timestamp without time zone NOT NULL,
    user_id bigint NOT NULL,
    type integer NOT NULL,
    ressource character varying(256) NOT NULL
);


--
-- TOC entry 1628 (class 1259 OID 43254)
-- Dependencies: 6
-- Name: feed2community; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE feed2community (
    id serial PRIMARY KEY,
    feed_id bigint NOT NULL,
    community_id bigint NOT NULL
);


--
-- TOC entry 1630 (class 1259 OID 43262)
-- Dependencies: 1969 6
-- Name: feedpolicygroup; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE feedpolicygroup (
    id serial PRIMARY KEY,
    feed_id bigint NOT NULL,
    group_id bigint NOT NULL,
    policy smallint NOT NULL,
    date timestamp without time zone DEFAULT now()
);


--
-- TOC entry 1632 (class 1259 OID 43271)
-- Dependencies: 1971 6
-- Name: feedpolicyuser; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE feedpolicyuser (
    id serial PRIMARY KEY,
    feed_id bigint NOT NULL,
    user_id bigint NOT NULL,
    policy smallint NOT NULL,
    date timestamp without time zone DEFAULT now()
);


--
-- TOC entry 1594 (class 1259 OID 43075)
-- Dependencies: 1922 1923 1924 1925 1926 1927 6
-- Name: folder; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE folder (
    folder_id serial PRIMARY KEY,
    left_indice bigint NOT NULL,
    right_indice bigint NOT NULL,
    parent_id bigint DEFAULT 0::bigint NOT NULL,
    name character varying(256) NOT NULL,
    description text NOT NULL,
    view bigint DEFAULT 0::bigint NOT NULL,
    date_update timestamp without time zone DEFAULT now(),
    teaser character varying(250) DEFAULT ''::character varying,
    privacy_status integer DEFAULT 0 NOT NULL,
    uuid character varying(512) DEFAULT ''::character varying,
    date_creation timestamp without time zone
);


--
-- TOC entry 1596 (class 1259 OID 43089)
-- Dependencies: 1929 6
-- Name: folderpolicygroup; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE folderpolicygroup (
    id serial PRIMARY KEY,
    folder_id bigint NOT NULL,
    group_id bigint NOT NULL,
    policy smallint NOT NULL,
    date timestamp without time zone DEFAULT now()
);



--
-- TOC entry 1598 (class 1259 OID 43098)
-- Dependencies: 1931 6
-- Name: folderpolicyuser; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE folderpolicyuser (
    id serial PRIMARY KEY,
    folder_id bigint NOT NULL,
    user_id bigint NOT NULL,
    policy smallint NOT NULL,
    date timestamp without time zone DEFAULT now()
);


--
-- TOC entry 1600 (class 1259 OID 43107)
-- Dependencies: 6
-- Name: group; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE "group" (
    group_id serial PRIMARY KEY,
    community_id bigint NOT NULL,
    name character varying(256) NOT NULL
);


--
-- TOC entry 1602 (class 1259 OID 43115)
-- Dependencies: 1934 1935 1936 1937 1938 1939 6
-- Name: item; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE item (
    item_id serial PRIMARY KEY,
    name character varying(250) NOT NULL,
    date_update timestamp without time zone DEFAULT now(),
    description text NOT NULL,
    type integer NOT NULL,
    view bigint DEFAULT 0::bigint NOT NULL,
    download bigint DEFAULT 0::bigint NOT NULL,
    thumbnail character varying(256),
    sizebytes bigint DEFAULT 0::bigint NOT NULL,
    privacy_status integer DEFAULT 0 NOT NULL,
    uuid character varying(512) DEFAULT ''::character varying,
    date_creation timestamp without time zone
);



CREATE TABLE item2folder (
    id serial PRIMARY KEY,
    item_id bigint NOT NULL,
    folder_id bigint NOT NULL
);

--
-- TOC entry 1604 (class 1259 OID 43130)
-- Dependencies: 1941 6
-- Name: itempolicygroup; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE itempolicygroup (
    id serial PRIMARY KEY,
    item_id bigint NOT NULL,
    group_id bigint NOT NULL,
    policy smallint NOT NULL,
    date timestamp without time zone DEFAULT now()
);


-- TOC entry 1606 (class 1259 OID 43139)
-- Dependencies: 1943 6
-- Name: itempolicyuser; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE itempolicyuser (
    id serial PRIMARY KEY,
    item_id bigint NOT NULL,
    user_id bigint NOT NULL,
    policy smallint NOT NULL,
    date timestamp without time zone DEFAULT now()
);


--
-- TOC entry 1612 (class 1259 OID 43164)
-- Dependencies: 1947 1948 6
-- Name: itemrevision; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE itemrevision (
    itemrevision_id serial PRIMARY KEY,
    item_id bigint NOT NULL,
    revision integer NOT NULL,
    date timestamp without time zone NOT NULL,
    changes text NOT NULL,
    user_id integer NOT NULL,
    license integer DEFAULT 0 NOT NULL,
    uuid character varying(512) DEFAULT ''::character varying
);

--
-- TOC entry 1616 (class 1259 OID 43183)
-- Dependencies: 1951 6
-- Name: metadata; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE metadata (
    metadata_id serial PRIMARY KEY,
    metadatatype integer DEFAULT 0 NOT NULL,
    element character varying(256) NOT NULL,
    qualifier character varying(256) NOT NULL,
    description character varying(512) NOT NULL
);


--
-- TOC entry 1618 (class 1259 OID 43194)
-- Dependencies: 6
-- Name: metadatadocumentvalue; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE metadatadocumentvalue (
    id serial PRIMARY KEY,
    metadata_id bigint NOT NULL,
    itemrevision_id bigint NOT NULL,
    value character varying(1024) NOT NULL,
    metadatavalue_id integer NOT NULL
);


--
-- TOC entry 1620 (class 1259 OID 43213)
-- Dependencies: 6
-- Name: metadatavalue; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE metadatavalue (
    id serial PRIMARY KEY,
    metadata_id bigint NOT NULL,
    itemrevision_id bigint NOT NULL,
    value character varying(1024) NOT NULL,
    metadatavalue_id integer NOT NULL
);

INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','contributor','author','Author of the data');
INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','date','uploaded','Date when the data was uploaded');
INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','date','issued','Date when the data was released');
INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','date','created','Date when the data was created');
INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','identifier','citation','Citation of the data');
INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','identifier','uri','URI identifier');
INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','identifier','pubmed','PubMed identifier');
INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','identifier','doi','Digital Object Identifier');
INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','description','general','General description field');
INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','description','provenance','Provenance of the data');
INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','description','sponsorship','Sponsor of the data');
INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','description','publisher','Publisher of the data');
INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','subject','keyword','Keyword');
INSERT INTO metadata (metadatatype,element,qualifier,description)
VALUES ('0','subject','ocis','OCIS subject');


--
-- TOC entry 1622 (class 1259 OID 43224)
-- Dependencies: 1957 1958 1959 1960 1961 1962 1963 1964 6
-- Name: user; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE "user" (
    user_id serial PRIMARY KEY,
    password character varying(100) NOT NULL,
    firstname character varying(256) NOT NULL,
    company character varying(256),
    thumbnail character varying(256),
    lastname character varying(256) NOT NULL,
    email character varying(256) NOT NULL,
    privacy integer DEFAULT 0 NOT NULL,
    admin integer DEFAULT 0 NOT NULL,
    view bigint DEFAULT 0::bigint NOT NULL,
    folder_id bigint,
    creation timestamp without time zone,
    publicfolder_id bigint,
    privatefolder_id bigint,
    uuid character varying(512) DEFAULT ''::character varying,
    city character varying(100) DEFAULT ''::character varying,
    country character varying(100) DEFAULT ''::character varying,
    website character varying(255) DEFAULT ''::character varying,
    biography character varying(255) DEFAULT ''::character varying,
    dynamichelp integer DEFAULT 1
);


--
-- TOC entry 1624 (class 1259 OID 43238)
-- Dependencies: 6
-- Name: user2group; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE user2group (
    id serial PRIMARY KEY,
    user_id bigint NOT NULL,
    group_id bigint NOT NULL
);


--
-- Settings Table
--
CREATE TABLE  setting (
    setting_id serial  PRIMARY KEY,
    name  character varying(256) NOT NULL,
    module  character varying(256) NOT NULL,
    value text NOT NULL
);
