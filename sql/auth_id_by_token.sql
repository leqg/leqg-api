SELECT `user`.`id`, `user`.`client` FROM `token` LEFT JOIN `user` ON `user`.`id` = `token`.`id` WHERE `token` = :token AND ( `begin` <= NOW() AND `end` >= NOW() )
