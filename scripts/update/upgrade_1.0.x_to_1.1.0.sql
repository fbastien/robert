
-- ----------------------------- --
-- Création des nouvelles tables --
-- ----------------------------- --

CREATE TABLE `robert_matos_unit` (
		`id_matosunit`   INT(6)       NOT NULL AUTO_INCREMENT,
		`id_matosdetail` INT(6)       NOT NULL,
		`ref`            VARCHAR(128) NOT NULL,
		`panne`          BOOL         NOT NULL,
		`dateAchat`      DATE         DEFAULT NULL,
		`ownerExt`       VARCHAR(256) DEFAULT NULL,
		`remarque`       TEXT         DEFAULT NULL,
		PRIMARY KEY (`id_matosunit`),
		UNIQUE KEY `ref` (`ref`),
		KEY `id_matosdetail` (`id_matosdetail`) )
	ENGINE = MyISAM,
	AUTO_INCREMENT = 1,
	DEFAULT CHARSET = utf8;

-- ----------------------- --
-- Modification des tables --
-- ----------------------- --

ALTER TABLE `robert_matos_detail`
	ADD COLUMN `codeBarres`
		VARCHAR(16) DEFAULT NULL
		AFTER `ref`,
	ADD UNIQUE KEY `codeBarres` (`codeBarres`),
	MODIFY COLUMN `panne`
		INT(4) NOT NULL,
	MODIFY COLUMN `dateAchat`
		DATE DEFAULT NULL,
	MODIFY COLUMN `ownerExt`
		VARCHAR(256) DEFAULT NULL,
	MODIFY COLUMN `remarque`
		TEXT DEFAULT NULL;
 
ALTER TABLE `robert_plans`
	ADD COLUMN `units`
		TEXT NOT NULL
		AFTER `materiel`;

ALTER TABLE `robert_plans_details`
	ADD COLUMN `units`
		TEXT NOT NULL
		AFTER `materiel`;

ALTER TABLE `robert_users`
	ADD COLUMN `ldap_uid`
		VARCHAR(255) DEFAULT NULL
		AFTER `id`,
	ADD UNIQUE KEY `ldap_uid` (`ldap_uid`),
	MODIFY COLUMN `password`
		VARCHAR(32) DEFAULT NULL;
-- TODO ajouter trigger

-- ---------------------- --
-- Conversion des données --
-- ---------------------- --

UPDATE `robert_matos_detail`
	SET `ownerExt` = NULL
	WHERE `externe` = 0;
UPDATE `robert_matos_detail`
	SET `dateAchat` = NULL
	WHERE `externe` = 1;
UPDATE `robert_matos_detail`
	SET `remarque` = NULL
	WHERE `remarque` = '';

-- ----------------------- --
-- Modification des tables --
-- ----------------------- --

ALTER TABLE `robert_matos_detail`
	DROP COLUMN `externe`;
