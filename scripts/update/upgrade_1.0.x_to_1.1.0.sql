
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
	MODIFY COLUMN `panne`
		INT(4) NOT NULL,
	MODIFY COLUMN `dateAchat`
		DATE DEFAULT NULL,
	MODIFY COLUMN `ownerExt`
		VARCHAR(256) DEFAULT NULL,
	MODIFY COLUMN `remarque`
		TEXT DEFAULT NULL;

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
