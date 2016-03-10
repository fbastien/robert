
-- ----------------------- --
-- Modification des tables --
-- ----------------------- --

ALTER TABLE `robert_users`
	ADD COLUMN `ldap_uid`
		VARCHAR(255) NULL
		AFTER `id`,
	ADD UNIQUE KEY `ldap_uid` (`ldap_uid`),
	MODIFY COLUMN `password`
		VARCHAR(32) NULL;
