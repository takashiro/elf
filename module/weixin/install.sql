
ALTER TABLE `pre_user` ADD `wxopenid` varchar(32) DEFAULT NULL;
ALTER TABLE `pre_user` ADD `wxunionid` varchar(32) DEFAULT NULL;
ALTER TABLE `pre_user` ADD UNIQUE KEY `wxopenid` (`wxopenid`);
ALTER TABLE `pre_user` ADD UNIQUE KEY `wxunionid` (`wxunionid`);

DROP TABLE IF EXISTS `pre_authkey`;
CREATE TABLE IF NOT EXISTS `pre_authkey` (
  `user` varchar(32) NOT NULL,
  `key` varchar(32) NOT NULL,
  `expiry` int(11) unsigned NOT NULL,
  PRIMARY KEY (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_autoreply`;
CREATE TABLE IF NOT EXISTS `pre_autoreply` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `keyword` text NOT NULL,
  `reply` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
