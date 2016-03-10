
-- ---------------------- --
-- Conversion des données --
-- ---------------------- --

UPDATE `robert_users`
	SET `password` = '061d36f3666ce4790c5cfb855fed4658' -- Le mot de passe devient "ldap"
	WHERE `password` IS NULL;

-- ----------------------- --
-- Restauration des tables --
-- ----------------------- --

ALTER TABLE `robert_users`
	DROP KEY `ldap_uid`,
	DROP COLUMN `ldap_uid`,
	MODIFY COLUMN `password`
		VARCHAR(32) NOT NULL;
