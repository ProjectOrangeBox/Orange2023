DROP TABLE IF EXISTS `main`;

CREATE TABLE `main` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(32) DEFAULT NULL,
  `last_name` varchar(32) DEFAULT NULL,
  `age` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `main` (`first_name`, `last_name`, `age`)
VALUES
	('Johnny', 'Appleseed', 28),
	('Jenny', 'Appleseed', 31);

DROP TABLE IF EXISTS `join`;

CREATE TABLE `join` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned DEFAULT NULL,
  `child_name` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB;

INSERT INTO `join` (`parent_id`, `child_name`)
VALUES
	(1, 'Peter'),
	(2, 'Sally'),
	(2, 'Chuck');

DROP TABLE IF EXISTS `crud`;

CREATE TABLE `crud` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(32) DEFAULT NULL,
  `last_name` varchar(32) DEFAULT NULL,
  `age` int unsigned DEFAULT NULL,
  `is_active` int unsigned DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `crud` (`first_name`, `last_name`, `age`)
VALUES
	('Johnny', 'Appleseed', 28),
	('Jenny', 'Appleseed', 31);