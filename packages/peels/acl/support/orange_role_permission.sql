CREATE TABLE `orange_role_permission` (
  `role_id` int(10) unsigned NOT NULL DEFAULT 0,
  `permission_id` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`role_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
