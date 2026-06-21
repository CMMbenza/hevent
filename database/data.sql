-- mot de passe: admin123
-- password_hash("admin123", PASSWORD_DEFAULT);

USE hevent;

-- =====================================================
-- UTILISATEUR TEST (ADMIN)
-- =====================================================

INSERT INTO users (fullname, phone, email, password, photo)
VALUES (
'Admin H-Event',
'+243810000000',
'[admin@hevent.com](mailto:admin@hevent.com)',
'$2y$10$examplehashedpassword1234567890', 
NULL
);

INSERT INTO packs (name, code, max_invites, price) VALUES

('Basic', 'BSC-9F3K2', 25, 0),
('Standard', 'STD-4X8LM', 50, 10),
('Premium', 'PRM-UNLTD', -1, 25);

-- =====================================================
-- EVENEMENT TEST
-- =====================================================

INSERT INTO events (
user_id,
title,
event_type,
event_date,
event_time,
location,
description,
cover_image,
status
)
VALUES (
1,
'Mariage Jean & Marie',
'Mariage',
'2026-12-20',
'15:00:00',
'Kinshasa - Salle Concordia',
'Célébration du mariage de Jean et Marie',
NULL,
'Publie'
);

-- =====================================================
-- TABLES TEST
-- =====================================================

INSERT INTO event_tables (event_id, table_name, capacity)
VALUES
(1, 'Table VIP', 8),
(1, 'Table A', 10),
(1, 'Table B', 10);

-- =====================================================
-- INVITES TEST
-- =====================================================

INSERT INTO invites (
event_id,
table_id,
fullname,
phone,
email,
invite_code,
qr_code,
invitation_token,
rsvp_status,
viewed,
checked_in
)
VALUES
(1, 1, 'Paul Kabila', '+243810111111', '[paul@gmail.com](mailto:paul@gmail.com)', 'INV-001', NULL, 'token-001', 'En attente', 0, 0),
(1, 1, 'Sarah Mbuyi', '+243810222222', '[sarah@gmail.com](mailto:sarah@gmail.com)', 'INV-002', NULL, 'token-002', 'Present', 1, 0),
(1, 2, 'David Kanza', '+243810333333', '[david@gmail.com](mailto:david@gmail.com)', 'INV-003', NULL, 'token-003', 'Absent', 1, 0),
(1, 3, 'Grace Lumbala', '+243810444444', '[grace@gmail.com](mailto:grace@gmail.com)', 'INV-004', NULL, 'token-004', 'En attente', 0, 0);

-- =====================================================
-- SCAN QR TEST
-- =====================================================

INSERT INTO qr_scans (invite_id)
VALUES
(2);

-- =====================================================
-- GALERIE TEST
-- =====================================================

INSERT INTO gallery (event_id, invite_id, photo)
VALUES
(1, 2, 'photo1.jpg'),
(1, 3, 'photo2.jpg');

-- =====================================================
-- LIVRE D'OR TEST
-- =====================================================

INSERT INTO guestbook (event_id, invite_id, message)
VALUES
(1, 2, 'Félicitations aux mariés ! Beaucoup de bonheur ❤️'),
(1, 3, 'Très belle cérémonie, merci pour l’invitation !');
