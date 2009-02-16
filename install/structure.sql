#
# QuickFox Setup SQL template [structure section]. 
#
# Table definition for {DBKEY}acc_groups 
DROP TABLE IF EXISTS `{DBKEY}acc_groups` ;
CREATE TABLE `{DBKEY}acc_groups` ( 
    `id` tinyint(5) unsigned NOT NULL auto_increment, 
    `name` varchar(32) NOT NULL, 
    `descr` varchar(255) NOT NULL, 
    `manager` int(10) unsigned NOT NULL default '1', 
    PRIMARY KEY (`id`) , 
    UNIQUE `name` (`name`) , 
    INDEX `manager` (`manager`)  
); 

# Table definition for {DBKEY}acc_links 
DROP TABLE IF EXISTS `{DBKEY}acc_links` ;
CREATE TABLE `{DBKEY}acc_links` ( 
    `user_id` int(10) unsigned NOT NULL default '0', 
    `group_id` tinyint(5) unsigned NOT NULL default '0', 
    `time_given` int(11) NOT NULL default '0', 
    `drop_after` int(11) default NULL, 
    PRIMARY KEY (`user_id`, `group_id`) , 
    INDEX `drop_after` (`drop_after`)  
); 

# Table definition for {DBKEY}bans 
DROP TABLE IF EXISTS `{DBKEY}bans` ;
CREATE TABLE `{DBKEY}bans` ( 
    `ban_id` int(10) unsigned NOT NULL auto_increment, 
    `first_ip` varchar(8) NOT NULL, 
    `last_ip` varchar(8) NOT NULL, 
    `reason` varchar(255) NOT NULL, 
    `used` int(10) unsigned NOT NULL default '0', 
    `lastused` int(11) default NULL, 
    PRIMARY KEY (`ban_id`) , 
    INDEX `ips` (`first_ip`, `last_ip`)  
); 

# Table definition for {DBKEY}config 
DROP TABLE IF EXISTS `{DBKEY}config` ;
CREATE TABLE `{DBKEY}config` ( 
    `parent` varchar(10) NOT NULL, 
    `name` varchar(32) NOT NULL, 
    `value` text NOT NULL, 
    PRIMARY KEY (`parent`, `name`)  
); 

# Table definition for {DBKEY}dloads 
DROP TABLE IF EXISTS `{DBKEY}dloads` ;
CREATE TABLE `{DBKEY}dloads` ( 
    `fileid` varchar(32) NOT NULL, 
    `filecode` varchar(32) NOT NULL, 
    `user` varchar(16) NOT NULL, 
    `session` varchar(32) NOT NULL, 
    `ip` varchar(16) NOT NULL, 
    `time` int(11) NOT NULL default '0', 
    `used` tinyint(1) unsigned NOT NULL default '0', 
    PRIMARY KEY (`filecode`) , 
    UNIQUE `fileid` (`fileid`, `filecode`) , 
    INDEX `user` (`user`)  
); 

# Table definition for {DBKEY}files 
DROP TABLE IF EXISTS `{DBKEY}files` ;
CREATE TABLE `{DBKEY}files` ( 
    `id` varchar(32) NOT NULL, 
    `folder` int(10) unsigned NOT NULL default '0', 
    `att_to` int(10) unsigned NOT NULL default '0', 
    `user` varchar(16) NOT NULL, 
    `user_id` int(11) NOT NULL default '0', 
    `time` int(11) NOT NULL default '0', 
    `file` varchar(32) NOT NULL, 
    `filename` varchar(255) NOT NULL, 
    `ext` varchar(10) NOT NULL, 
    `size` int(10) unsigned NOT NULL default '0', 
    `caption` varchar(255) NOT NULL, 
    `descr` text NOT NULL, 
    `rights` tinyint(1) unsigned NOT NULL default '1', 
    `dloads` int(10) unsigned NOT NULL default '0', 
    PRIMARY KEY (`id`) , 
    INDEX `folder` (`folder`) , 
    INDEX `att_to` (`att_to`) , 
    INDEX `user_id` (`user_id`) , 
    INDEX `rights` (`rights`)  
); 

# Table definition for {DBKEY}guests 
DROP TABLE IF EXISTS `{DBKEY}guests` ;
CREATE TABLE `{DBKEY}guests` ( 
    `gid` varchar(32) NOT NULL, 
    `gnick` varchar(15) NOT NULL, 
    `gtimezone` decimal(5,2) default NULL, 
    `gstyle` varchar(16) NOT NULL, 
    `sessid` varchar(32) NOT NULL, 
    `lastseen` int(11) NOT NULL default '0', 
    `lasturl` varchar(255) NOT NULL, 
    `lastip` varchar(16) NOT NULL, 
    `guser_agent` varchar(255) NOT NULL, 
    `gcode` varchar(32) NOT NULL, 
    `views` int(10) unsigned NOT NULL default '0', 
    PRIMARY KEY (`gid`) , 
    INDEX `lastseen` (`lastseen`) , 
    INDEX `gcode` (`gcode`)  
); 

# Table definition for {DBKEY}mime 
DROP TABLE IF EXISTS `{DBKEY}mime` ;
CREATE TABLE `{DBKEY}mime` ( 
    `ext` varchar(10) NOT NULL, 
    `type` varchar(255) NOT NULL, 
    `descr` varchar(255) NOT NULL, 
    `preview` tinyint(1) unsigned NOT NULL default '0', 
    `icon` varchar(50) NOT NULL, 
    PRIMARY KEY (`ext`) , 
    INDEX `preview` (`preview`)  
); 

# Table definition for {DBKEY}minichats 
DROP TABLE IF EXISTS `{DBKEY}minichats` ;
CREATE TABLE `{DBKEY}minichats` ( 
    `msg_id` int(10) unsigned NOT NULL auto_increment, 
    `author` varchar(16) NOT NULL, 
    `author_id` int(10) unsigned NOT NULL default '0', 
    `text` text NOT NULL, 
    `time` int(11) NOT NULL default '0', 
    `acc_lv` tinyint(1) unsigned NOT NULL default '0', 
    PRIMARY KEY (`msg_id`) , 
    INDEX `time` (`time`) , 
    INDEX `author_id` (`author_id`)  
); 

# Table definition for {DBKEY}parchive 
DROP TABLE IF EXISTS `{DBKEY}parchive` ;
CREATE TABLE `{DBKEY}parchive` ( 
    `id` int(10) unsigned NOT NULL default '0', 
    `content` text NOT NULL, 
    PRIMARY KEY (`id`)  
); 

# Table definition for {DBKEY}pms 
DROP TABLE IF EXISTS `{DBKEY}pms` ;
CREATE TABLE `{DBKEY}pms` ( 
    `id` int(10) unsigned NOT NULL auto_increment, 
    `time` int(11) NOT NULL default '0', 
    `author` varchar(16) NOT NULL, 
    `author_id` int(10) unsigned NOT NULL default '0', 
    `recipient` varchar(16) NOT NULL, 
    `recipient_id` int(10) unsigned NOT NULL default '0', 
    `theme` varchar(255) NOT NULL, 
    `text` text NOT NULL, 
    `readed` tinyint(1) unsigned NOT NULL default '0', 
    `deleted` tinyint(1) unsigned NOT NULL default '0', 
    PRIMARY KEY (`id`) , 
    INDEX `author_id` (`author_id`) , 
    INDEX `recipient_id` (`recipient_id`) , 
    INDEX `state` (`readed`, `deleted`)  
); 

# Table definition for {DBKEY}posts 
DROP TABLE IF EXISTS `{DBKEY}posts` ;
CREATE TABLE `{DBKEY}posts` ( 
    `id` int(10) unsigned NOT NULL auto_increment, 
    `theme` int(10) unsigned NOT NULL default '0', 
    `author` varchar(16) NOT NULL, 
    `author_id` int(10) unsigned NOT NULL default '0', 
    `text` text NOT NULL, 
    `hash` varchar(32) NOT NULL, 
    `time` int(11) NOT NULL default '0', 
    `ctime` int(11) NOT NULL default '0', 
    `changer` varchar(16) NOT NULL, 
    `locked` tinyint(1) unsigned NOT NULL default '0', 
    `deleted` tinyint(1) unsigned NOT NULL default '0', 
    PRIMARY KEY (`id`) , 
    INDEX `author_id` (`author_id`) , 
    INDEX `theme` (`theme`) , 
    INDEX `hash` (`hash`) , 
    INDEX `deleted` (`deleted`) , 
    FULLTEXT `text` (`text`)  
); 

# Table definition for {DBKEY}posts_cache 
DROP TABLE IF EXISTS `{DBKEY}posts_cache` ;
CREATE TABLE `{DBKEY}posts_cache` ( 
    `ch_id` int(10) unsigned NOT NULL default '0', 
    `ch_text` text NOT NULL, 
    `ch_stored` int(11) NOT NULL default '0', 
    PRIMARY KEY (`ch_id`) , 
    INDEX `ch_stored` (`ch_stored`)  
); 

# Table definition for {DBKEY}reads 
DROP TABLE IF EXISTS `{DBKEY}reads` ;
CREATE TABLE `{DBKEY}reads` ( 
    `user_id` int(10) unsigned NOT NULL default '0', 
    `theme` int(10) unsigned NOT NULL default '0', 
    `lastread` int(10) unsigned NOT NULL default '0', 
    `active` tinyint(1) unsigned NOT NULL default '0', 
    `subscribe` tinyint(1) unsigned NOT NULL default '0', 
    `notified` tinyint(1) unsigned NOT NULL default '0', 
    `lastmail` int(11) NOT NULL default '0', 
    PRIMARY KEY (`user_id`, `theme`) , 
    INDEX `state` (`active`, `subscribe`, `notified`) , 
    INDEX `lastmail` (`lastmail`)  
); 

# Table definition for {DBKEY}regs 
DROP TABLE IF EXISTS `{DBKEY}regs` ;
CREATE TABLE `{DBKEY}regs` ( 
    `nick` varchar(16) NOT NULL, 
    `time` int(11) NOT NULL default '0', 
    `pass` varchar(32) NOT NULL, 
    `email` varchar(36) NOT NULL, 
    `descr` text NOT NULL, 
    `acode` varchar(32) NOT NULL, 
    `echecked` tinyint(1) unsigned NOT NULL default '0', 
    PRIMARY KEY (`nick`) , 
    INDEX `echecked` (`echecked`)  
); 

# Table definition for {DBKEY}results 
DROP TABLE IF EXISTS `{DBKEY}results` ;
CREATE TABLE `{DBKEY}results` ( 
    `id` varchar(32) NOT NULL, 
    `time` int(11) NOT NULL default '0', 
    `error` text NOT NULL, 
    `result` text NOT NULL, 
    `redirect` varchar(255) NOT NULL, 
    PRIMARY KEY (`id`)  
); 

# Table definition for {DBKEY}sections 
DROP TABLE IF EXISTS `{DBKEY}sections` ;
CREATE TABLE `{DBKEY}sections` ( 
    `id` int(10) unsigned NOT NULL auto_increment, 
    `parent` int(10) unsigned NOT NULL default '0', 
    `name` varchar(128) NOT NULL, 
    `descr` varchar(255) NOT NULL, 
    `minrights` tinyint(1) unsigned NOT NULL default '0', 
    `postrights` tinyint(1) unsigned NOT NULL default '1', 
    `acc_group` tinyint(5) unsigned NOT NULL default '0', 
    `order_id` int(10) unsigned NOT NULL default '0', 
    `locked` tinyint(1) unsigned NOT NULL default '0', 
    `deleted` tinyint(1) unsigned NOT NULL default '0', 
    PRIMARY KEY (`id`) , 
    INDEX `parent` (`parent`) , 
    INDEX `rights` (`minrights`, `postrights`) , 
    INDEX `state` (`locked`, `deleted`) , 
    INDEX `acc_group` (`acc_group`) , 
    INDEX `order_id` (`order_id`) , 
    FULLTEXT `text` (`name`, `descr`)  
); 

# Table definition for {DBKEY}sess_cache 
DROP TABLE IF EXISTS `{DBKEY}sess_cache` ;
CREATE TABLE `{DBKEY}sess_cache` ( 
    `sid` varchar(32) NOT NULL, 
    `ch_name` varchar(32) NOT NULL, 
    `ch_data` text NOT NULL, 
    `ch_stored` int(11) NOT NULL default '0', 
    PRIMARY KEY (`sid`, `ch_name`) , 
    INDEX `ch_stored` (`ch_stored`)  
); 

# Table definition for {DBKEY}sessions 
DROP TABLE IF EXISTS `{DBKEY}sessions` ;
CREATE TABLE `{DBKEY}sessions` ( 
    `sid` varchar(32) NOT NULL, 
    `ip` varchar(16) NOT NULL, 
    `vars` text NOT NULL, 
    `starttime` int(11) NOT NULL default '0', 
    `lastused` int(11) NOT NULL default '0', 
    `clicks` int(5) unsigned NOT NULL default '0', 
    `spamcode` varchar(10) NOT NULL, 
    `spctime` int(11) NOT NULL default '0', 
    PRIMARY KEY (`sid`) , 
    INDEX `ip` (`ip`) , 
    INDEX `lastused` (`lastused`)  
); 

# Table definition for {DBKEY}smiles 
DROP TABLE IF EXISTS `{DBKEY}smiles` ;
CREATE TABLE `{DBKEY}smiles` ( 
    `id` int(10) unsigned NOT NULL auto_increment, 
    `sm_text` varchar(16) NOT NULL, 
    `sm_icon` varchar(16) NOT NULL, 
    `sm_capt` varchar(16) NOT NULL, 
    PRIMARY KEY (`id`) , 
    UNIQUE `sm_text` (`sm_text`) , 
    INDEX `sm_icon` (`sm_icon`)  
); 

# Table definition for {DBKEY}spiders 
DROP TABLE IF EXISTS `{DBKEY}spiders` ;
CREATE TABLE `{DBKEY}spiders` ( 
    `id` int(10) unsigned NOT NULL auto_increment, 
    `agent_mask` varchar(255) NOT NULL, 
    `name` varchar(32) NOT NULL, 
    PRIMARY KEY (`id`) , 
    UNIQUE `agent_mask` (`agent_mask`)  
); 

# Table definition for {DBKEY}spiders_log 
DROP TABLE IF EXISTS `{DBKEY}spiders_log` ;
CREATE TABLE `{DBKEY}spiders_log` ( 
    `log_id` int(10) unsigned NOT NULL auto_increment, 
    `name` varchar(32) NOT NULL, 
    `time` int(11) NOT NULL default '0', 
    `query` varchar(255) NOT NULL, 
    `user_agent` varchar(255) NOT NULL, 
    `ip` varchar(16) NOT NULL, 
    PRIMARY KEY (`log_id`) , 
    INDEX `name` (`name`) , 
    INDEX `ip` (`ip`) , 
    INDEX `time` (`time`)  
); 

# Table definition for {DBKEY}spiders_stats 
DROP TABLE IF EXISTS `{DBKEY}spiders_stats` ;
CREATE TABLE `{DBKEY}spiders_stats` ( 
    `id` int(10) unsigned NOT NULL default '0', 
    `lastseen` int(11) NOT NULL default '0', 
    `visits` int(10) unsigned NOT NULL default '0', 
    PRIMARY KEY (`id`) , 
    INDEX `lastseen` (`lastseen`)  
); 

# Table definition for {DBKEY}styles 
DROP TABLE IF EXISTS `{DBKEY}styles` ;
CREATE TABLE `{DBKEY}styles` ( 
    `id` varchar(4) NOT NULL, 
    `name` varchar(16) NOT NULL, 
    `visual` varchar(16) NOT NULL, 
    `CSS` varchar(16) NOT NULL, 
    PRIMARY KEY (`id`) , 
    UNIQUE `name` (`name`)  
); 

# Table definition for {DBKEY}topics 
DROP TABLE IF EXISTS `{DBKEY}topics` ;
CREATE TABLE `{DBKEY}topics` ( 
    `id` int(10) unsigned NOT NULL auto_increment, 
    `parent` int(10) unsigned NOT NULL default '0', 
    `author` varchar(16) NOT NULL, 
    `author_id` int(10) unsigned NOT NULL default '0', 
    `name` varchar(255) default NULL, 
    `descr` text NOT NULL, 
    `minrights` tinyint(1) unsigned NOT NULL default '0', 
    `postrights` tinyint(1) unsigned NOT NULL default '1', 
    `time` int(11) NOT NULL default '0', 
    `lasttime` int(11) NOT NULL default '0', 
    `lastposter` varchar(16) NOT NULL, 
    `lastposter_id` int(10) unsigned NOT NULL default '0', 
    `posts` int(10) unsigned NOT NULL default '0', 
    `MaxID` int(10) unsigned NOT NULL default '0', 
    `locked` tinyint(1) unsigned NOT NULL default '0', 
    `special` tinyint(1) unsigned NOT NULL default '0', 
    `pinned` tinyint(1) unsigned NOT NULL default '0', 
    `deleted` tinyint(1) unsigned NOT NULL default '0', 
    PRIMARY KEY (`id`) , 
    INDEX `parent` (`parent`) , 
    INDEX `author_id` (`author_id`) , 
    INDEX `lastposter_id` (`lastposter_id`) , 
    INDEX `rights` (`minrights`, `postrights`) , 
    INDEX `state` (`locked`, `special`, `pinned`, `deleted`) , 
    INDEX `MaxID` (`MaxID`) , 
    FULLTEXT `text` (`name`, `descr`)  
); 

# Table definition for {DBKEY}users 
DROP TABLE IF EXISTS `{DBKEY}users` ;
CREATE TABLE `{DBKEY}users` ( 
    `id` int(10) unsigned NOT NULL auto_increment, 
    `nick` varchar(16) NOT NULL, 
    `pass` varchar(32) NOT NULL, 
    `passdropcode` varchar(32) NOT NULL, 
    `email` varchar(36) NOT NULL, 
    `new_email` varchar(36) NOT NULL, 
    `new_acode` varchar(32) NOT NULL, 
    `homepage` varchar(50) NOT NULL, 
    `sex` char(1) NOT NULL, 
    `descr` varchar(128) NOT NULL, 
    `icq` varchar(36) NOT NULL, 
    `city` varchar(128) NOT NULL, 
    `about` text NOT NULL, 
    `more_fields` text NOT NULL, 
    `avatar` varchar(64) NOT NULL, 
    `greet` varchar(30) NOT NULL, 
    `timezone` decimal(5,2) default NULL, 
    `style` varchar(16) NOT NULL, 
    `noemailpm` tinyint(1) unsigned NOT NULL default '0', 
    `subscrtype` tinyint(1) unsigned NOT NULL default '1', 
    `active` tinyint(1) unsigned NOT NULL default '1', 
    `hasnewpm` tinyint(1) unsigned NOT NULL default '0', 
    `hasnewsubscr` tinyint(1) unsigned NOT NULL default '0', 
    `regtime` int(11) NOT NULL default '0', 
    `sessid` varchar(32) NOT NULL, 
    `autologin` varchar(32) NOT NULL, 
    `lastseen` int(11) NOT NULL default '0', 
    `lasturl` varchar(255) NOT NULL, 
    `lastip` varchar(16) NOT NULL, 
    `rights` tinyint(1) unsigned NOT NULL default '1', 
    `modlevel` tinyint(1) unsigned NOT NULL default '0', 
    `admin` tinyint(1) unsigned NOT NULL default '0', 
    `deleted` tinyint(1) unsigned NOT NULL default '0', 
    PRIMARY KEY (`id`) , 
    UNIQUE `nick` (`nick`) , 
    INDEX `lastseen` (`lastseen`) , 
    INDEX `subscrtype` (`subscrtype`) , 
    INDEX `active` (`active`) , 
    INDEX `rights` (`rights`, `modlevel`, `admin`)  
); 

# Table definition for {DBKEY}userstats 
DROP TABLE IF EXISTS `{DBKEY}userstats` ;
CREATE TABLE `{DBKEY}userstats` ( 
    `user_id` int(10) unsigned NOT NULL default '0', 
    `posts` int(10) unsigned NOT NULL default '0', 
    `themes` int(10) unsigned NOT NULL default '0', 
    `files` int(10) unsigned NOT NULL default '0', 
    `lastposttime` int(11) NOT NULL default '0', 
    `lasttheme` int(10) unsigned NOT NULL default '0', 
    `lastpost` int(10) unsigned NOT NULL default '0', 
    PRIMARY KEY (`user_id`)  
); 

