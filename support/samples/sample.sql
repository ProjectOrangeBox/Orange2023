#
# SQL Export
# Created by Querious (300063)
# Created: September 10, 2023 at 11:35:03 AM EDT
# Encoding: Unicode (UTF-8)
#

# Set the host, database, username, and password in .env

SET @ORIG_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

SET @ORIG_UNIQUE_CHECKS = @@UNIQUE_CHECKS;
SET UNIQUE_CHECKS = 0;

SET @ORIG_TIME_ZONE = @@TIME_ZONE;
SET TIME_ZONE = '+00:00';

SET @ORIG_SQL_MODE = @@SQL_MODE;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';



CREATE TABLE `childern` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(128) DEFAULT NULL,
  `age` int(4) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;


CREATE TABLE `people` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL,
  `age` int(4) unsigned DEFAULT NULL,
  `phone` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;




LOCK TABLES `childern` WRITE;
TRUNCATE `childern`;
INSERT INTO `childern` (`id`, `parent_id`, `name`, `age`) VALUES 
	(1,1,'Jake',5),
	(2,1,'Jenny',3);
UNLOCK TABLES;


LOCK TABLES `people` WRITE;
TRUNCATE `people`;
INSERT INTO `people` (`id`, `name`, `age`, `phone`) VALUES 
	(1,'Johnny Appleseed',29,'123-123-1234');
UNLOCK TABLES;






SET FOREIGN_KEY_CHECKS = @ORIG_FOREIGN_KEY_CHECKS;

SET UNIQUE_CHECKS = @ORIG_UNIQUE_CHECKS;

SET @ORIG_TIME_ZONE = @@TIME_ZONE;
SET TIME_ZONE = @ORIG_TIME_ZONE;

SET SQL_MODE = @ORIG_SQL_MODE;



# Export Finished: September 10, 2023 at 11:35:03 AM EDT

