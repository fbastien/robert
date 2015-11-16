
-- ----------------------------- --
-- Création des nouvelles tables --
-- ----------------------------- --

CREATE TABLE `robert_matos_generique` (
		`id_matosdetail` INT(6) NOT NULL,
		`quantite` INT(4) NOT NULL,
		`panne` INT(3) NOT NULL,
		`dateAchat` DATE DEFAULT NULL,
		`ownerExt` VARCHAR(256) DEFAULT NULL,
		PRIMARY KEY (`id_matosdetail`) )
	ENGINE = MyISAM,
	DEFAULT CHARSET = utf8;

CREATE TABLE `robert_matos_ident` (
		`id_matosident` INT(6) NOT NULL AUTO_INCREMENT,
		`id_matosdetail` INT(6) NOT NULL,
		`ref` VARCHAR(128) NOT NULL,
		`panne` BOOL NOT NULL,
		`dateAchat` DATE DEFAULT NULL,
		`ownerExt` VARCHAR(256) DEFAULT NULL,
		`remarque` TEXT DEFAULT NULL,
		PRIMARY KEY (`id_matosident`),
		UNIQUE KEY `ref` (`ref`),
		KEY `id_matosdetail` (`id_matosdetail`) )
	ENGINE = MyISAM,
	AUTO_INCREMENT = 1,
	DEFAULT CHARSET = utf8;

-- ---------------------- --
-- Conversion des données --
-- ---------------------- --

INSERT INTO `robert_matos_generique` (`id_matosdetail`, `quantite`, `panne`, `dateAchat`, `ownerExt`)
	SELECT `id`, `Qtotale`, `panne`, IF(`externe` = 0, `dateAchat`, NULL), IF(`externe` = 1, `ownerExt`, NULL)
	FROM `robert_matos_detail`;

-- ----------------------- --
-- Modification des tables --
-- ----------------------- --

ALTER TABLE `robert_matos_detail`
	DROP COLUMN `panne`;
ALTER TABLE `robert_matos_detail`
	DROP COLUMN `externe`;
ALTER TABLE `robert_matos_detail`
	DROP COLUMN `Qtotale`;
ALTER TABLE `robert_matos_detail`
	DROP COLUMN `dateAchat`;
ALTER TABLE `robert_matos_detail`
	DROP COLUMN `ownerExt`;
ALTER TABLE `robert_matos_detail`
	MODIFY COLUMN `remarque` TEXT DEFAULT NULL;

-- ---------------------- --
-- Conversion des données --
-- ---------------------- --

UPDATE `robert_matos_detail`
	SET `remarque` = NULL
	WHERE `remarque` = '';

-- --------------------------- --
-- Création des nouvelles vues --
-- --------------------------- --

CREATE VIEW `robert_view_sum_matos_ident`
	AS SELECT `id_matosdetail`,
			COUNT(*) AS `quantite`,
			COUNT( IF(`panne` = TRUE, `id_matosident`, NULL) ) AS `panne`,
			COUNT( IF(`ownerExt` IS NOT NULL, `id_matosident`, NULL) ) AS `externe`,
			MIN(`dateAchat`) AS `dateAchat`,
			GROUP_CONCAT(DISTINCT `ownerExt` ORDER BY `ownerExt` ASC SEPARATOR ', ') AS `ownerExt`
		FROM `robert_matos_ident`
		GROUP BY `id_matosdetail`;

CREATE VIEW `robert_view_matos_detail`
	AS SELECT `id`, `label`, `ref`,
			(IFNULL(`robert_view_sum_matos_ident`.`panne`, 0) + IFNULL(`robert_matos_generique`.`panne`, 0)) AS `panne`,
			IF(IFNULL(`robert_view_sum_matos_ident`.`externe`, 0) + IF(`robert_matos_generique`.`ownerExt` IS NULL, 0, `robert_matos_generique`.`quantite`)
					> (IFNULL(`robert_view_sum_matos_ident`.`quantite`, 0) + IFNULL(`robert_matos_generique`.`quantite`, 0)) / 2,
				1, 0) AS `externe`,
			`categorie`, `sousCateg`,
			(IFNULL(`robert_view_sum_matos_ident`.`quantite`, 0) + IFNULL(`robert_matos_generique`.`quantite`, 0)) AS `Qtotale`,
			`tarifLoc`, `valRemp`,
			IF(IFNULL(`robert_view_sum_matos_ident`.`externe`, 0) + IF(`robert_matos_generique`.`ownerExt` IS NULL, 0, `robert_matos_generique`.`quantite`)
					<= (IFNULL(`robert_view_sum_matos_ident`.`quantite`, 0) + IFNULL(`robert_matos_generique`.`quantite`, 0)) / 2,
				LEAST(IFNULL(`robert_view_sum_matos_ident`.`dateAchat`, `robert_matos_generique`.`dateAchat`), IFNULL(`robert_matos_generique`.`dateAchat`, `robert_view_sum_matos_ident`.`dateAchat`)),
				NULL) AS `dateAchat`,
			IF(IFNULL(`robert_view_sum_matos_ident`.`externe`, 0) + IF(`robert_matos_generique`.`ownerExt` IS NULL, 0, `robert_matos_generique`.`quantite`)
					> (IFNULL(`robert_view_sum_matos_ident`.`quantite`, 0) + IFNULL(`robert_matos_generique`.`quantite`, 0)) / 2,
				IF(`robert_view_sum_matos_ident`.`ownerExt` IS NOT NULL AND `robert_matos_generique`.`ownerExt` IS NOT NULL,
					CONCAT(`robert_matos_generique`.`ownerExt`, ', ', `robert_view_sum_matos_ident`.`ownerExt`),
					COALESCE(`robert_view_sum_matos_ident`.`ownerExt`, `robert_matos_generique`.`ownerExt`)),
				NULL) AS `ownerExt`,
			`remarque`
		FROM `robert_matos_detail`
			LEFT JOIN `robert_view_sum_matos_ident`
				ON `id` = `robert_view_sum_matos_ident`.`id_matosdetail`
			LEFT JOIN `robert_matos_generique`
				ON `id` = `robert_matos_generique`.`id_matosdetail`;
