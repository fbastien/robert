
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

ALTER DATABASE
	CHARACTER SET = 'utf8'
	COLLATE = 'utf8_unicode_ci';

-- TABLE benef_interlocuteurs

DROP TABLE IF EXISTS `robert_benef_interlocuteurs`;

CREATE TABLE `robert_benef_interlocuteurs` (
		`id`          INT(4)       NOT NULL AUTO_INCREMENT,
		`label`       VARCHAR(128) NOT NULL,
		`idStructure` INT(4)       NOT NULL,
		`nomPrenom`   VARCHAR(64)  NOT NULL,
		`adresse`     VARCHAR(128) NOT NULL,
		`codePostal`  VARCHAR(10)  NOT NULL,
		`ville`       VARCHAR(64)  NOT NULL,
		`email`       VARCHAR(128) NOT NULL,
		`tel`         VARCHAR(14)  NOT NULL,
		`poste`       VARCHAR(128) NOT NULL,
		`remarque`    TEXT         NOT NULL,
		`nomStruct`   VARCHAR(64)  NOT NULL,
		`typeRetour`  VARCHAR(64)  NOT NULL,
		PRIMARY KEY (`id`),
		KEY `label` (`label`),
		KEY `nomPrenom` (`nomPrenom`),
		KEY `email` (`email`) )
	ENGINE = MyISAM,
	AUTO_INCREMENT = 1,
	DEFAULT CHARSET = utf8;

-- TABLE benef_structure

DROP TABLE IF EXISTS `robert_benef_structure`;

CREATE TABLE `robert_benef_structure` (
		`id`            INT(4)       NOT NULL AUTO_INCREMENT,
		`label`         VARCHAR(128) NOT NULL,
		`SIRET`         VARCHAR(64)  NOT NULL,
		`type`          VARCHAR(64)  NOT NULL,
		`NomRS`         VARCHAR(128) NOT NULL,
		`interlocteurs` VARCHAR(256) NOT NULL,
		`adresse`       VARCHAR(128) NOT NULL,
		`codePostal`    VARCHAR(8)   NOT NULL,
		`ville`         VARCHAR(64)  NOT NULL,
		`email`         VARCHAR(64)  NOT NULL,
		`tel`           VARCHAR(14)  NOT NULL,
		`nbContrats`    INT(3)       NOT NULL,
		`listePlans`    VARCHAR(512) NOT NULL,
		`decla`         VARCHAR(256) NOT NULL,
		`remarque`      TEXT         NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `label` (`label`),
		KEY `SIRET` (`SIRET`) )
	ENGINE = MyISAM,
	AUTO_INCREMENT = 1,
	DEFAULT CHARSET = utf8;

-- TABLE devis

DROP TABLE IF EXISTS `robert_devis`;

CREATE TABLE `robert_devis` (
		`id`       INT(6)        NOT NULL AUTO_INCREMENT,
		`id_plan`  INT(6)        NOT NULL,
		`numDevis` INT(3)        NOT NULL,
		`fichier`  VARCHAR(128)  NOT NULL,
		`matos`    VARCHAR(1024) NOT NULL,
		`tekos`    VARCHAR(256)  NOT NULL,
		`total`    FLOAT         NOT NULL,
		PRIMARY KEY (`id`),
		KEY `fichier` (`fichier`) )
	ENGINE = MyISAM,
	AUTO_INCREMENT = 1,
	DEFAULT CHARSET = utf8;

-- TABLE matos_detail

DROP TABLE IF EXISTS `robert_matos_detail`;

CREATE TABLE `robert_matos_detail` (
		`id`        INT(6)       NOT NULL AUTO_INCREMENT,
		`label`     VARCHAR(256) NOT NULL,
		`ref`       VARCHAR(128) NOT NULL,
		`panne`     INT(4)       NOT NULL,
		`categorie` VARCHAR(128) NOT NULL,
		`sousCateg` INT(4)       NOT NULL,
		`Qtotale`   INT(4)       NOT NULL,
		`tarifLoc`  FLOAT        NOT NULL,
		`valRemp`   FLOAT        NOT NULL,
		`dateAchat` DATE         DEFAULT NULL,
		`ownerExt`  VARCHAR(256) DEFAULT NULL,
		`remarque`  TEXT         DEFAULT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `ref` (`ref`),
		KEY `sousCateg` (`sousCateg`) )
	ENGINE = MyISAM,
	AUTO_INCREMENT = 1,
	DEFAULT CHARSET = utf8;

-- TABLE matos_packs

DROP TABLE IF EXISTS `robert_matos_packs`;

CREATE TABLE `robert_matos_packs` (
		`id`        INT(4)       NOT NULL AUTO_INCREMENT,
		`label`     VARCHAR(256) NOT NULL,
		`ref`       VARCHAR(128) NOT NULL,
		`categorie` VARCHAR(128) NOT NULL,
		`externe`   TINYINT(1)   NOT NULL,
		`tarifLoc`  FLOAT        NOT NULL,
		`valRemp`   FLOAT        NOT NULL,
		`detail`    VARCHAR(256) NOT NULL,
		`remarque`  TEXT         NOT NULL,
		PRIMARY KEY (`id`) )
	ENGINE = MyISAM,
	AUTO_INCREMENT = 1,
	DEFAULT CHARSET = utf8;

-- TABLE matos_sous_cat

DROP TABLE IF EXISTS `robert_matos_sous_cat`;

CREATE TABLE `robert_matos_sous_cat` (
		`id`    INT(4)       NOT NULL AUTO_INCREMENT,
		`label` VARCHAR(256) NOT NULL,
		`ordre` INT(4)       NOT NULL,
		PRIMARY KEY (`id`),
		KEY `ordre` (`ordre`) )
	ENGINE = MyISAM,
	AUTO_INCREMENT = 23,
	DEFAULT CHARSET = utf8;

INSERT INTO `robert_matos_sous_cat` (`id`, `label`, `ordre`)
VALUES
	(1, 'Amplificateurs', 1),
	(2, 'Enceintes', 2),
	(3, 'Consoles son', 3),
	(4, 'Périphériques son', 4),
	(5, 'Microphones', 5),
	(6, 'Pieds micro', 6),
	(7, 'Câbles son', 7),
	(8, 'Divers son', 8),
	(9, 'Pieds enceinte et lumière', 9),
	(10, 'Structure', 10),
	(11, 'Pendrillons', 11),
	(12, 'Divers structure', 12),
	(13, 'Distribution électrique', 13),
	(14, 'Divers élec', 14),
	(15, 'Console lumière', 15),
	(16, 'Projecteurs', 16),
	(17, 'Gradateurs', 17),
	(18, 'Câbles lumière', 18),
	(19, 'Divers lumière', 19),
	(20, 'Divers', 20),
	(21, 'Véhicules', 21),
	(22, 'Divers transport', 22);

-- TABLE matos_unit

DROP TABLE IF EXISTS `robert_matos_unit`;

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

-- TABLE notes

DROP TABLE IF EXISTS `robert_notes`;

CREATE TABLE `robert_notes` (
		`id`        INT(9)       NOT NULL AUTO_INCREMENT,
		`date`      INT(10)      NOT NULL,
		`texte`     TEXT         NOT NULL,
		`createur`  VARCHAR(128) NOT NULL,
		`important` TINYINT(1)   NOT NULL,
		PRIMARY KEY (`id`),
		KEY `date` (`date`) )
	ENGINE = MyISAM,
	AUTO_INCREMENT = 1,
	DEFAULT CHARSET = utf8;

-- TABLE plans

DROP TABLE IF EXISTS `robert_plans`;

CREATE TABLE `robert_plans` (
		`id`           INT(11)      NOT NULL AUTO_INCREMENT,
		`idGroup`      INT(11)      NOT NULL,
		`titre`        VARCHAR(64)  NOT NULL,
		`lieu`         VARCHAR(128) NOT NULL,
		`date_start`   TINYTEXT     NOT NULL,
		`date_end`     TINYTEXT     NOT NULL,
		`createur`     VARCHAR(256) NOT NULL,
		`beneficiaire` VARCHAR(64)  NOT NULL,
		`techniciens`  VARCHAR(64)  NOT NULL,
		`materiel`     TEXT         NOT NULL,
		`confirm`      VARCHAR(15)  NOT NULL DEFAULT '0',
		UNIQUE KEY `id` (`id`),
		KEY `titre` (`titre`) )
	ENGINE = MyISAM,
	AUTO_INCREMENT = 1,
	DEFAULT CHARSET = utf8;

-- TABLE plans_details

DROP TABLE IF EXISTS `robert_plans_details`;

CREATE TABLE `robert_plans_details` (
		`id_plandetails`   INT(11)      NOT NULL AUTO_INCREMENT,
		`id_plan`          INT(11)      NOT NULL,
		`jour`             VARCHAR(64)  NOT NULL,
		`techniciens`      VARCHAR(100) NOT NULL,
		`materiel`         TEXT         NOT NULL,
		`details_remarque` MEDIUMTEXT   NOT NULL,
		PRIMARY KEY (`id_plandetails`) )
	ENGINE = MyISAM,
	AUTO_INCREMENT = 1,
	DEFAULT CHARSET = utf8;

-- TABLE tekos

DROP TABLE IF EXISTS `robert_tekos`;

CREATE TABLE `robert_tekos` (
		`id`              INT(3)       NOT NULL AUTO_INCREMENT,
		`idUser`          SMALLINT(3)  NOT NULL,
		`surnom`          VARCHAR(128) NOT NULL,
		`nom`             VARCHAR(128) NOT NULL,
		`prenom`          VARCHAR(128) NOT NULL,
		`email`           VARCHAR(128) NOT NULL,
		`tel`             VARCHAR(20)  NOT NULL,
		`GUSO`            VARCHAR(128) NOT NULL,
		`CS`              VARCHAR(128) NOT NULL,
		`birthDay`        DATE         NOT NULL,
		`birthPlace`      VARCHAR(256) NOT NULL,
		`habilitations`   VARCHAR(256) NOT NULL,
		`categorie`       VARCHAR(128) NOT NULL,
		`SECU`            VARCHAR(128) NOT NULL,
		`SIRET`           VARCHAR(128) NOT NULL,
		`assedic`         VARCHAR(64)  NOT NULL,
		`intermittent`    TINYINT(1)   NOT NULL,
		`adresse`         VARCHAR(64)  NOT NULL,
		`cp`              VARCHAR(64)  NOT NULL,
		`ville`           VARCHAR(64)  NOT NULL,
		`diplomes_folder` VARCHAR(64)  NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `surnom` (`surnom`),
		KEY `GUSO` (`GUSO`),
		KEY `CS` (`CS`),
		KEY `SECU` (`SECU`) )
	ENGINE = MyISAM,
	AUTO_INCREMENT = 1,
	DEFAULT CHARSET = utf8;

-- TABLE users

DROP TABLE IF EXISTS `robert_users`;

CREATE TABLE `robert_users` (
		`id`                  INT(10)      NOT NULL AUTO_INCREMENT,
		`ldap_uid`            VARCHAR(255) NULL,
		`email`               VARCHAR(255) NOT NULL,
		`password`            VARCHAR(32)  NULL,
		`nom`                 CHAR(30)     NOT NULL,
		`prenom`              CHAR(30)     NOT NULL,
		`level`               INT(1)       NOT NULL DEFAULT '1',
		`date_inscription`    INT(10)      NOT NULL,
		`date_last_action`    INT(10)      NOT NULL,
		`date_last_connexion` INT(10)      NOT NULL,
		`theme`               VARCHAR(32)  NOT NULL,
		`yeux`                VARCHAR(64)  NOT NULL,
		`cheveux`             VARCHAR(64)  NOT NULL,
		`age`                 INT(2)       NOT NULL,
		`taille`              FLOAT        NOT NULL,
		`idTekos`             SMALLINT(3)  NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `ldap_uid` (`ldap_uid`),
		UNIQUE KEY `email` (`email`) )
	ENGINE = MyISAM,
	AUTO_INCREMENT = 2,
	DEFAULT CHARSET = utf8;

INSERT INTO `robert_users` (`id`, `ldap_uid`, `email`, `password`, `nom`, `prenom`, `level`, `date_inscription`, `date_last_action`, `date_last_connexion`, `theme`, `yeux`, `cheveux`, `age`, `taille`, `idTekos`)
VALUES (1, NULL, 'root@robertmanager.org', '8351aaf8480d8135bc77af590c93c1e2', 'DEBUGGER', 'Root', '9', 1325615980, 1356632988, 1356620371, 'human', 'blancs', 'rouges', 31, 1.73, 0);
