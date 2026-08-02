CREATE TABLE IF NOT EXISTS `mc_news_event` (
    `id_news` int UNSIGNED NOT NULL,
    `registration_enabled` tinyint(1) NOT NULL DEFAULT 0,
    `max_participants` int UNSIGNED DEFAULT 0,
    PRIMARY KEY (`id_news`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mc_news_registration` (
    `id_registration` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_news` int UNSIGNED NOT NULL,
    `firstname` varchar(150) NOT NULL,
    `lastname` varchar(150) NOT NULL,
    `email` varchar(255) NOT NULL,
    `phone` varchar(50) DEFAULT NULL,
    `date_register` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_registration`),
    KEY `idx_news_reg` (`id_news`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;