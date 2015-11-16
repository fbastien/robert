
-- ---------------------- --
-- Conversion des données --
-- ---------------------- --

UPDATE `robert_matos_detail`
	SET `remarque` = ''
	WHERE `remarque` IS NULL;

-- ----------------------- --
-- Restauration des tables --
-- ----------------------- --

ALTER TABLE `robert_matos_detail`
	ADD COLUMN `panne` INT(3) NOT NULL
	AFTER `ref`;
ALTER TABLE `robert_matos_detail`
	ADD COLUMN `externe` TINYINT(1) NOT NULL
	AFTER `panne`;
ALTER TABLE `robert_matos_detail`
	ADD COLUMN `Qtotale` INT(4) NOT NULL
	AFTER `sousCateg`;
ALTER TABLE `robert_matos_detail`
	ADD COLUMN `dateAchat` DATE NOT NULL
	AFTER `valRemp`;
ALTER TABLE `robert_matos_detail`
	ADD COLUMN `ownerExt` VARCHAR(256) NOT NULL
	AFTER `dateAchat`;
ALTER TABLE `robert_matos_detail`
	MODIFY COLUMN `remarque` TEXT NOT NULL;

-- ---------------------- --
-- Conversion des données --
-- ---------------------- --

UPDATE `robert_matos_detail`
		JOIN `robert_view_matos_detail`
			USING (`id`)
	SET `robert_matos_detail`.`panne` = `robert_view_matos_detail`.`panne`,
		`robert_matos_detail`.`externe` = `robert_view_matos_detail`.`externe`,
		`robert_matos_detail`.`Qtotale` = `robert_view_matos_detail`.`Qtotale`,
		`robert_matos_detail`.`dateAchat` = `robert_view_matos_detail`.`dateAchat`,
		`robert_matos_detail`.`ownerExt` = `robert_view_matos_detail`.`ownerExt`;

-- ------------------------------ --
-- Suppression des nouvelles vues --
-- ------------------------------ --

DROP VIEW `robert_view_matos_detail`;

DROP VIEW `robert_view_sum_matos_ident`;

-- -------------------------------- --
-- Suppression des nouvelles tables --
-- -------------------------------- --

DROP TABLE `robert_matos_generique`;

DROP TABLE `robert_matos_ident`;
