
ALTER TABLE `pre_user` ADD `wallet` decimal(9,2) NOT NULL;

DROP TABLE IF EXISTS `pre_prepaidreward`;
CREATE TABLE IF NOT EXISTS `pre_prepaidreward` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `minamount` decimal(9,2) NOT NULL,
  `maxamount` decimal(9,2) NOT NULL,
  `reward` decimal(9,2) NOT NULL,
  `etime_start` int(11) unsigned NOT NULL,
  `etime_end` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_userwalletlog`;
CREATE TABLE IF NOT EXISTS `pre_userwalletlog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL,
  `dateline` int(11) unsigned NOT NULL,
  `type` tinyint(4) unsigned NOT NULL,
  `delta` decimal(9,2) NOT NULL,
  `cost` decimal(9,2) NOT NULL,
  `recharged` tinyint(1) NOT NULL,
  `orderid` mediumint(8) unsigned NOT NULL,
  `paymentmethod` tinyint(4) NOT NULL,
  `tradeid` varchar(255) NOT NULL,
  `tradestate` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
