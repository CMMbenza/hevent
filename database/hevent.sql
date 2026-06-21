CREATE TABLE `events` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `title` varchar(255) NOT NULL,
 `event_type` enum('Mariage','Anniversaire','Bapteme','Soutenance','Conference','Reunion','Personnalise') NOT NULL,
 `event_date` date NOT NULL,
 `event_time` time NOT NULL,
 `location` varchar(255) NOT NULL,
 `description` text DEFAULT NULL,
 `cover_image` varchar(255) DEFAULT NULL,
 `STATUS` enum('Brouillon','Publie','Termine') DEFAULT 'Brouillon',
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 KEY `user_id` (`user_id`),
 CONSTRAINT `events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
event_tables	CREATE TABLE `event_tables` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `event_id` int(11) NOT NULL,
 `TABLE_NAME` varchar(100) NOT NULL,
 `capacity` int(11) NOT NULL DEFAULT 0,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 KEY `event_id` (`event_id`),
 CONSTRAINT `event_tables_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
gallery	CREATE TABLE `gallery` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `event_id` int(11) NOT NULL,
 `invite_id` int(11) DEFAULT NULL,
 `photo` varchar(255) NOT NULL,
 `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 KEY `event_id` (`event_id`),
 KEY `invite_id` (`invite_id`),
 CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
 CONSTRAINT `gallery_ibfk_2` FOREIGN KEY (`invite_id`) REFERENCES `invites` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
guestbook	CREATE TABLE `guestbook` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `event_id` int(11) NOT NULL,
 `invite_id` int(11) DEFAULT NULL,
 `message` text NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 KEY `event_id` (`event_id`),
 KEY `invite_id` (`invite_id`),
 CONSTRAINT `guestbook_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
 CONSTRAINT `guestbook_ibfk_2` FOREIGN KEY (`invite_id`) REFERENCES `invites` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
invites	CREATE TABLE `invites` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `event_id` int(11) NOT NULL,
 `table_id` int(11) DEFAULT NULL,
 `fullname` varchar(150) NOT NULL,
 `phone` varchar(30) DEFAULT NULL,
 `email` varchar(150) DEFAULT NULL,
 `invite_code` varchar(50) NOT NULL,
 `qr_code` varchar(255) DEFAULT NULL,
 `invitation_token` varchar(100) DEFAULT NULL,
 `rsvp_status` enum('En attente','Present','Absent') DEFAULT 'En attente',
 `viewed` tinyint(1) DEFAULT 0,
 `checked_in` tinyint(1) DEFAULT 0,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `invite_code` (`invite_code`),
 UNIQUE KEY `invitation_token` (`invitation_token`),
 KEY `event_id` (`event_id`),
 KEY `table_id` (`table_id`),
 CONSTRAINT `invites_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
 CONSTRAINT `invites_ibfk_2` FOREIGN KEY (`table_id`) REFERENCES `event_tables` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
packs	CREATE TABLE `packs` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `NAME` enum('Basic','Standard','Premium') NOT NULL,
 `CODE` varchar(20) NOT NULL,
 `max_invites` int(11) NOT NULL,
 `price` decimal(10,2) DEFAULT 0.00,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `CODE` (`CODE`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
qr_scans	CREATE TABLE `qr_scans` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `invite_id` int(11) NOT NULL,
 `scanned_at` datetime DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 KEY `invite_id` (`invite_id`),
 CONSTRAINT `qr_scans_ibfk_1` FOREIGN KEY (`invite_id`) REFERENCES `invites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
users	CREATE TABLE `users` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `fullname` varchar(150) NOT NULL,
 `phone` varchar(30) DEFAULT NULL,
 `email` varchar(150) NOT NULL,
 `PASSWORD` varchar(255) NOT NULL,
 `photo` varchar(255) DEFAULT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`id`),
 UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci