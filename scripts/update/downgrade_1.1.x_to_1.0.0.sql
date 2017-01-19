
-- ----------------------- --
-- Restauration des tables --
-- ----------------------- --

ALTER TABLE `robert_matos_detail`
	ADD COLUMN `externe`
		TINYINT(1) NOT NULL
		AFTER `panne`;

-- ---------------------- --
-- Conversion des données --
-- ---------------------- --

UPDATE `robert_matos_detail`
	SET `externe` = IF(`ownerExt` IS NULL, 0, 1),
		`dateAchat` = IF(`externe` = 0,
			IF(`dateAchat` IS NULL
					AND NOT EXISTS (
						SELECT *
						FROM `robert_matos_unit`
						WHERE `dateAchat` IS NOT NULL),
				'0000-00-00',
				LEAST(IFNULL(`dateAchat`, '9999-99-99'), 
					IFNULL((
							SELECT MIN(`dateAchat`)
							FROM `robert_matos_unit`
							WHERE `id_matosdetail` = `id`),
						'9999-99-99'))),
			'0000-00-00'),
		`ownerExt` = IF(`externe` = 1,
			CONCAT_WS(', ', `ownerExt`, (
				SELECT GROUP_CONCAT(DISTINCT `ownerExt`
					ORDER BY `ownerExt` ASC
					SEPARATOR ', ')
				FROM `robert_matos_unit`
				GROUP BY `id_matosdetail`
				HAVING `id_matosdetail` = `id`)),
			''),
		`remarque` = CONCAT_WS('\n', `remarque`, (
				SELECT GROUP_CONCAT(CONCAT(`ref`, ' : ', `remarque`)
					SEPARATOR '\n')
				FROM `robert_matos_unit`
				WHERE `id_matosdetail` = `id`));

UPDATE `robert_users`
	SET `password` = '061d36f3666ce4790c5cfb855fed4658' -- Le mot de passe devient "ldap"
	WHERE `password` IS NULL;

-- ----------------------- --
-- Restauration des tables --
-- ----------------------- --

ALTER TABLE `robert_matos_detail`
	MODIFY COLUMN `panne`
		INT(3) NOT NULL,
	MODIFY COLUMN `dateAchat`
		DATE NOT NULL,
	MODIFY COLUMN `ownerExt`
		VARCHAR(256) NOT NULL,
	MODIFY COLUMN `remarque`
		TEXT NOT NULL;

ALTER TABLE `robert_plans`
	DROP COLUMN `units`;

ALTER TABLE `robert_plans_details`
	DROP COLUMN `units`;

ALTER TABLE `robert_users`
	DROP KEY `ldap_uid`,
	DROP COLUMN `ldap_uid`,
	MODIFY COLUMN `password`
		VARCHAR(32) NOT NULL;

-- -------------------------------- --
-- Suppression des nouvelles tables --
-- -------------------------------- --

DROP TABLE `robert_matos_unit`;
