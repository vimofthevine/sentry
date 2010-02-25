CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL auto_increment,
    `username` varchar(32) NOT NULL,
    `password` varchar(50) NOT NULL,
    `role` varchar(16) NOT NULL,
    `token` varchar(32) NOT NULL,
    `last_login` int(10) NOT NULL,
    `logins` int(10) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

