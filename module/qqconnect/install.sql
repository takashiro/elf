
ALTER TABLE `pre_user` ADD `qqopenid` varchar(32) DEFAULT NULL;
ALTER TABLE `pre_user` ADD UNIQUE KEY `qqopenid` (`qqopenid`);
