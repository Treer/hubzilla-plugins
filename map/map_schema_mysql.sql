
--
-- Table structure for table `locserv-dynamic-markers`
--

CREATE TABLE IF NOT EXISTS `locserv-dynamic-markers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` char(255) NOT NULL DEFAULT '',
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `lat` decimal(32,16),
  `lon` decimal(32,16),
  `accuracy` decimal(32,16),
  `heading` decimal(32,16),
  `speed` decimal(32,16),
  `layer` char(255) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `edited` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `resource_id` (`resource_id`),
  KEY `aid` (`aid`),
  KEY `uid` (`uid`),
  FULLTEXT KEY `allow_cid` (`allow_cid`),
  FULLTEXT KEY `allow_gid` (`allow_gid`),
  FULLTEXT KEY `deny_cid` (`deny_cid`),
  FULLTEXT KEY `deny_gid` (`deny_gid`),
  KEY `lat` (`lat`),
  KEY `lon` (`lon`),
  KEY `accuracy` (`accuracy`),
  KEY `heading` (`heading`),
  KEY `speed` (`speed`),
  KEY `layer` (`layer`),
  KEY `created` (`created`),
  KEY `edited` (`edited`),
  KEY `expires` (`expires`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------


--
-- Table structure for table `locserv-static-markers`
--

CREATE TABLE IF NOT EXISTS `locserv-static-markers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` char(255) NOT NULL DEFAULT '',
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `lat` decimal(32,16),
  `lon` decimal(32,16),
  `layer` char(255) NOT NULL DEFAULT '',
  `title` text NOT NULL,
  `body` mediumtext NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `edited` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `resource_id` (`resource_id`),
  KEY `aid` (`aid`),
  KEY `uid` (`uid`),
  FULLTEXT KEY `allow_cid` (`allow_cid`),
  FULLTEXT KEY `allow_gid` (`allow_gid`),
  FULLTEXT KEY `deny_cid` (`deny_cid`),
  FULLTEXT KEY `deny_gid` (`deny_gid`),
  KEY `lat` (`lat`),
  KEY `lon` (`lon`),
  KEY `layer` (`layer`),
  FULLTEXT KEY `title` (`title`),
  FULLTEXT KEY `body` (`body`),
  KEY `created` (`created`),
  KEY `edited` (`edited`),
  KEY `expires` (`expires`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------


--
-- Table structure for table `locserv-layers`
--

CREATE TABLE IF NOT EXISTS `locserv-layers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` char(255) NOT NULL DEFAULT '',
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `title` text NOT NULL,
  `body` mediumtext NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `edited` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `resource_id` (`resource_id`),
  KEY `aid` (`aid`),
  KEY `uid` (`uid`),
  FULLTEXT KEY `allow_cid` (`allow_cid`),
  FULLTEXT KEY `allow_gid` (`allow_gid`),
  FULLTEXT KEY `deny_cid` (`deny_cid`),
  FULLTEXT KEY `deny_gid` (`deny_gid`),
  FULLTEXT KEY `title` (`title`),
  FULLTEXT KEY `body` (`body`),
  KEY `created` (`created`),
  KEY `edited` (`edited`),
  KEY `expires` (`expires`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------