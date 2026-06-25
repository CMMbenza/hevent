#25.06
CREATE TABLE `event_drinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `generat_event` int(11) NOT NULL,
  `drink_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `generat_event` (`generat_event`),
  CONSTRAINT `fk_event_drinks_event` FOREIGN KEY (`generat_event`) REFERENCES `events` (`generat`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `guest_drink_choices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invite_id` int(11) NOT NULL,
  `drink_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `invite_id` (`invite_id`),
  KEY `drink_id` (`drink_id`),
  CONSTRAINT `fk_choice_invite` FOREIGN KEY (`invite_id`) REFERENCES `invites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_choice_drink` FOREIGN KEY (`drink_id`) REFERENCES `event_drinks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;