CREATE TABLE `mod_minsk115_authors` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) DEFAULT NULL,
    `telegram_id` varchar(255) DEFAULT NULL,
    `bot_state` varchar(255) DEFAULT NULL,
    `is_no_moderate_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `is_admin_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `is_banned_sw` enum('Y','N') NOT NULL DEFAULT 'N',
    `date_last_activity` timestamp NULL DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_minsk115_orders` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `author_id` int unsigned DEFAULT NULL,
    `ext_id` int unsigned DEFAULT NULL,
    `ext_city_id` int unsigned DEFAULT NULL,
    `status` enum('draft','moderate','moderate_115','new','active','in_process','closed','rejected') NOT NULL DEFAULT 'moderate',
    `nmbr` varchar(30) DEFAULT NULL,
    `user_comment` varchar(1000) DEFAULT NULL,
    `subject` varchar(1000) DEFAULT NULL,
    `result_text` mediumtext,
    `address` varchar(255) DEFAULT NULL,
    `lat` varchar(100) DEFAULT NULL,
    `lng` varchar(100) DEFAULT NULL,
    `rating` int unsigned DEFAULT NULL,
    `date_order` timestamp NULL DEFAULT NULL,
    `moderate_message` varchar(255) DEFAULT NULL,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `author_id` (`author_id`),
    KEY `ext_id` (`ext_id`),
    KEY `nmbr` (`nmbr`),
    CONSTRAINT `fk1_mod_minsk115_orders` FOREIGN KEY (`author_id`) REFERENCES `mod_minsk115_authors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `mod_minsk115_orders_comments` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `order_id` int unsigned NOT NULL,
    `ext_id` int DEFAULT NULL,
    `status` varchar(255) DEFAULT NULL,
    `creator` varchar(255) DEFAULT NULL,
    `emergency_mark` varchar(255) DEFAULT NULL,
    `comment` text,
    `author` varchar(255) DEFAULT NULL,
    `date_event` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `ext_id` (`ext_id`),
    CONSTRAINT `fk1_mod_minsk115_orders_comments` FOREIGN KEY (`order_id`) REFERENCES `mod_minsk115_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_minsk115_orders_files` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `ext_id` int unsigned DEFAULT NULL,
    `refid` int unsigned NOT NULL,
    `content` longblob,
    `filename` varchar(255) NOT NULL,
    `filesize` int NOT NULL,
    `hash` varchar(128) NOT NULL,
    `status` enum('pending','processing','error','completed') NOT NULL DEFAULT 'pending',
    `error_text` varchar(255) DEFAULT NULL,
    `type` varchar(20) DEFAULT NULL,
    `fieldid` varchar(255) DEFAULT NULL,
    `thumb` longblob,
    `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `refid` (`refid`),
    KEY `ext_id` (`ext_id`),
    CONSTRAINT `fk1_mod_minsk115_orders_files` FOREIGN KEY (`refid`) REFERENCES `mod_minsk115_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;