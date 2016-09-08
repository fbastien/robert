<?php
/*
 *
Le Robert est un logiciel libre; vous pouvez le redistribuer et/ou
le modifier sous les termes de la Licence Publique Générale GNU Affero
comme publiée par la Free Software Foundation;
version 3.0.

Cette WebApp est distribuée dans l'espoir qu'elle soit utile,
mais SANS AUCUNE GARANTIE; sans même la garantie implicite de
COMMERCIALISATION ou D'ADAPTATION A UN USAGE PARTICULIER.
Voir la Licence Publique Générale GNU Affero pour plus de détails.

Vous devriez avoir reçu une copie de la Licence Publique Générale
GNU Affero avec les sources du logiciel; si ce n'est pas le cas,
rendez-vous à http://www.gnu.org/licenses/agpl.txt (en Anglais)
*
*/

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'ArrayDataSet.class.php';
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'PageTestCase.class.php';

/** Test des actions de sauvegarde et de restauration de base de données, et d'import de matériel (fct/SQL_backup.php). */
class SQL_Backup_Test extends PageTestCase {
	
	/** @var array Liste des tables dont le contenu est à tester. */
	private static $tables = array('robert_matos_detail', 'robert_matos_unit', 'robert_matos_sous_cat');
	
	/** @see PHPUnit_Framework_TestCase::setUpBeforeClass() */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		
		self::setTestedPage('fct'.DIRECTORY_SEPARATOR.'SQL_backup.php', array('importPath', 'bdd'));
		defined('FOLDER_IMPORT_MATOS') or define('FOLDER_IMPORT_MATOS', 'tests/fct/');
	}
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	public function setUp() {
		parent::setUp();
		$this->sessionConnectAdmin();
	}
	
	/**
	 * Teste l'appel à la page sans passer par le formulaire.
	 * 
	 * @test
	 */
	public function testNoPost() {
		// Test
		$this->callTestedPage();
		
		// Vérification du message d'erreur
		// TODO PHPUnit5+ : Utiliser expectOutputString() si le problème d'encodage est résolu
		$this->assertSame("accès interdit...", $this->getOutput());
	}
	
	/**
	 * Teste l'import pour un fichier d'inventaire qui n'existe plus.
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportInexistantFile() {
		// Préparation des données
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = 'SQL_backup_importInexistantFile.csv';
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message d'erreur
		$this->assertSame("FICHIER INTROUVABLE ! Impossible d'importer l'inventaire...", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire ne contenant aucune ligne valide.
	 * 
	 * Cas de test :
	 * 1. Ligne vide
	 * 2. Ligne sans séparateurs
	 * 3. Ligne avec types de séparateurs invalides (virgule, deux-points, et tabulation)
	 * 4. Ligne avec nombre de séparateurs invalide (12 et 14)
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportInvalidFormat() {
		// Préparation des données
		$file = 'SQL_backup_importInvalidFormat.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes de commentaires.
	 * 
	 * Cas de test (sur le premier champ) :
	 * 1. Lettres uniquement
	 * 2. Chaîne commençant par un chiffre
	 * 3. Nombre négatif
	 * 4. Nombre avec exposant
	 * 5. Chaîne valide à l'exception d'une espace devant
	 * 6. Chaîne valide à l'exception d'une espace derrière
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportComments() {
		// Préparation des données
		$file = 'SQL_backup_importComments.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes en erreur car elles n'ont ni référence ni code-barres.
	 * 
	 * Cas de test :
	 * 1. Les 2e et 3e champs sont vides
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de lignes d'ajout de nouveau matériel générique et unitaire, et de modification de matériel existant
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportInvalidReference() {
		// Préparation des données
		$file = 'SQL_backup_importInvalidReference.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		$lineCount = count(file(__DIR__.DIRECTORY_SEPARATOR.$file, FILE_SKIP_EMPTY_LINES));
		for($row = 1; $row <= $lineCount; $row++) {
			$this->assertContains("WARNING ligne $row ignorée : code-barres et/ou référence manquants.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes en erreur car la référence ou le code-barres existe déjà.
	 * 
	 * Cas de test :
	 * 1. Modification d'un matériel existant avec un code-barres est déjà utilisé
	 * 2. Modification d'un matériel existant avec une référence est déjà utilisée
	 * 3. Ajout d'un matériel unitaire, pour un matériel existant, dont le code-barres est déjà utilisé
	 * 4. Ajout d'un matériel unitaire, pour un matériel existant, dont la référence est déjà utilisée
	 * 5. Utilisation pour le matériel générique de code-barres déjà utilisés par d'autres matériels génériques
	 * 6. Utilisation pour le matériel générique de code-barres déjà utilisés par du matériel unitaire
	 * 7. Utilisation pour le matériel unitaire de code-barres déjà utilisés par du matériel générique
	 * 8. Utilisation pour le matériel unitaire de code-barres déjà utilisés par d'autres matériels unitaires
	 * 9. Modification d'un matériel existant en réutilisant le même code-barres et la même référence
	 * 10. Utilisation d'un code-barres déjà utilisé avant le passage du script mais libéré dans une ligne précédente
	 * 11. Utilisation d'une référence déjà utilisée avant le passage du script mais libérée dans une ligne précédente
	 * 12. Ajout d'un nouveau matériel dont le code-barres est déjà utilisé
	 * 13. Ajout d'un nouveau matériel dont la référence est déjà utilisée
	 * 14. Ajout d'un matériel unitaire, pour un nouveau matériel, dont le code-barres est déjà utilisé
	 * 15. Ajout d'un matériel unitaire, pour un nouveau matériel, dont la référence est déjà utilisée
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @depends testImportNewMatosNoUnit
	 * @depends testImportUpdateMatosNotAllUnits
	 * @covers ::importInventaire
	 */
	public function testImportDuplicateReference() {
		// Préparation des données
		$file = "SQL_backup_importDuplicateReference.csv";
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel remplissant les conditions de test pour les modifications de matériel existant
		$unchangingDataSet = new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'REF_GEN', // TODO code-barres CODE_GEN
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Reference et code-barres existants'),
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Doublon d'un code-barres generique"), //TODO revoir codes-barres
					array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::2',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Doublon d'un code-barres unitaire"),
					array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::3',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Doublon d'une reference generique"),
					array('id' => 8, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::4',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Doublon d'une reference unitaire"),
					array('id' => 9, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::5',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test'),
					array('id' => 10, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::6',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test'),
					array('id' => 11, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::7',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test'),
					array('id' => 12, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::8',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test'),
					array('id' => 13, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::9',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification sans changer le code-barres ni la reference')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 5, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 6, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 7, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 8, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 9, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 10, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 11, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 12, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 13, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 14, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 15, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null)),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 4, 'ref' => 'REF_UNIT', // TODO code-barres CODE_UNIT
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'))));
		$this->insertData($unchangingDataSet);
		$this->insertData(new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 14, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::10',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de code-barres avant prochain test'),
					array('id' => 15, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::11',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de reference avant prochain test')))));
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		foreach(array(1, 3, 10, 13, 29, 31, 38, 41) as $row) {
			// TODO (codes-barres)
			//$this->assertContains("WARNING ligne $row ignorée : code-barres déjà utilisé.<br />", $this->getOutput());
		}
		foreach(array(5, 7, 16, 19, 33, 35, 44, 47) as $row) {
			$this->assertContains("WARNING ligne $row ignorée : référence déjà utilisée.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que seules les lignes valides ont été ajoutées ou modifiées dans la base de données
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(),
				$unchangingDataSet, new ArrayDataSet(array(
					'robert_matos_detail' => array(
						array('id' => 14, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::10',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de code-barres avant prochain test'),
						array('id' => 15, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::11_NEW',
								'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de reference avant prochain test'),
						array('id' => 16, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::16',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Reutilisation d'ancien code-barres"),
						array('id' => 17, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::11',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Reutilisation d'ancienne reference"),
					// TODO (codes-barres)
					//	array('id' => 18, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::22',
					//		'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test'),
					//	array('id' => 19, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::24',
					//		'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test'),
						array('id' => 18 /* TODO 20 */, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::26',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test'),
						array('id' => 19 /* TODO 21 */, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDuplicateReference::28',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test')),
					'robert_matos_generique' => array(
						array('id_matosdetail' => 16, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 17, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					// TODO (codes-barres)
					//	array('id_matosdetail' => 18, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					//	array('id_matosdetail' => 19, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'))),
						array('id_matosdetail' => 18 /* TODO 20 */, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 19 /* TODO 21 */, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes en erreur car la quantité en panne est invalide.
	 * 
	 * Cas de test (sur le 8e champ) :
	 * 1. Nombre négatif
	 * 2. Chaîne commençant par un chiffre
	 * 3. Nombre décimal
	 * 4. Nombre avec séparateur de milliers
	 * 5. Caractère Unicode non-ASCII représentant un chiffre
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de lignes d'ajout de nouveau matériel générique et unitaire, et de modification de matériel existant
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportInvalidPanne() {
		// Préparation des données
		$file = 'SQL_backup_importInvalidPanne.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		$lineCount = count(file(__DIR__.DIRECTORY_SEPARATOR.$file, FILE_SKIP_EMPTY_LINES));
		for($row = 1; $row <= $lineCount; $row++) {
			$this->assertContains("WARNING ligne $row ignorée : quantité en panne invalide.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes en erreur car la date d'achat est renseignée en même temps que le prestataire externe.
	 * 
	 * Cas de test :
	 * 1. Les 12e et 13e champs ne sont pas vides
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de lignes d'ajout de nouveau matériel générique et unitaire, et de modification de matériel existant
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportInvalidDateAndExt() {
		// Préparation des données
		$file = 'SQL_backup_importInvalidDateAndExt.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		$lineCount = count(file(__DIR__.DIRECTORY_SEPARATOR.$file, FILE_SKIP_EMPTY_LINES));
		for($row = 1; $row <= $lineCount; $row++) {
			$this->assertContains("WARNING ligne $row ignorée : date d'achat et prestataire externe renseignés en même temps.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes en erreur car la date d'achat est invalide.
	 * 
	 * Cas de test (sur le 12e champ) :
	 * 1. Simple entier
	 * 2. Chaîne de caractères
	 * 3. Date avec des séparateurs invalides (slashes, espaces et points)
	 * 4. Date dans le mauvais ordre (jour-mois-année et année-jour-mois)
	 * 5. Date littérale (avec des espaces comme séparateurs, le mois en toutes lettres et l'année en dernier)
	 * 6. Date avec numéros de mois et de jour invalides
	 * 7. Date avec séparateurs et ordre corrects mais le mois en toutes lettres et le jour avec suffixe
	 * 8. Date avec nombres raccourcis (année sur 2 chiffres et mois et jour sur 1 chiffre)
	 * 9. Date dont le jour n'existe pas pour le mois
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de lignes d'ajout de nouveau matériel générique et unitaire, et de modification de matériel existant
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportInvalidDate() {
		// Préparation des données
		$file = 'SQL_backup_importInvalidDate.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		$lineCount = count(file(__DIR__.DIRECTORY_SEPARATOR.$file, FILE_SKIP_EMPTY_LINES));
		for($row = 1; $row <= $lineCount; $row++) {
			$this->assertContains("WARNING ligne $row ignorée : date d'achat invalide.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes de matériel unitaire valides mais situées après des lignes en erreur.
	 * 
	 * Cas de test (pour une ligne d'ajout unitaire valide) :
	 * 1. En première ligne de fichier
	 * 2. Séparée de sa ligne générique par une ligne invalide
	 * 3. Séparée de sa ligne générique par une ligne de commentaire
	 * 4. Après une ligne de modification de matériel existant invalide
	 * 5. Après une ligne d'ajout de nouveau matériel générique invalide
	 * 6. Après une ligne d'ajout de matériel unitaire invalide (elle-même après une ligne unitaire valide)
	 * 7. Après une ligne invalide car le code-barres et la référence ne sont pas renseignés
	 * 8. Après une ligne invalide car le code-barres est déjà utilisé
	 * 9. Après une ligne invalide car la référence est déjà utilisée
	 * 10. Après une ligne invalide car la quantité en panne est invalide
	 * 11. Après une ligne invalide car la date d'achat et le prestataire externe sont renseignés en même temps
	 * 12. Après une ligne invalide car la date d'achat est invalide
	 * 13. Après une ligne d'ajout de matériel unitaire invalide car la quantité en panne est supérieure à 1
	 * 14. Après une ligne d'ajout de matériel unitaire invalide car la quantité en panne est supérieure à la quantité totale en panne
	 * 15. Après une ligne invalide car la quantité en panne est supérieure à la quantité totale
	 * 16. Après une ligne d'ajout de matériel unitaire invalide car il n'est pas en panne alors que tout le matériel non identifié restant est en panne
	 * 17. Après une ligne de modification de matériel existant invalide car la quantité en panne non identifiée devient supérieure à la quantité totale non identifiée
	 * 18. Après une ligne invalide car la désignation n'est pas renseignée
	 * 19. Après une ligne invalide car la catégorie est invalide
	 * 20. Après une ligne invalide car la quantité est invalide
	 * 21. Après une ligne invalide car le tarif de location est invalide
	 * 22. Après une ligne invalide car la valeur de remplacement est invalide
	 * 23. Après une ligne de modification de matériel invalide car l'id n'existe pas
	 * 24. Après une ligne de modification de matériel existant invalide car la quantité indiquée est inférieure à la quantité déjà identifiée unitairement
	 * 25. Après une ligne de modification de matériel existant invalide car la quantité en panne indiquée est inférieure à la quantité en panne déjà identifiée unitairement
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @depends testImportComments
	 * @depends testImportDuplicateReference
	 * @depends testImportInvalidCategorie
	 * @depends testImportInvalidDate
	 * @depends testImportInvalidDateAndExt
	 * @depends testImportInvalidFormat
	 * @depends testImportInvalidId
	 * @depends testImportInvalidLabel
	 * @depends testImportInvalidReference
	 * @depends testImportInvalidPanne
	 * @depends testImportInvalidQuantite
	 * @depends testImportInvalidTarif
	 * @depends testImportInvalidUnitPanne
	 * @depends testImportInvalidValeur
	 * @depends testImportLessPannesThanUnits
	 * @depends testImportLessQuantityThanUnits
	 * @depends testImportMorePannesThanQuantity
	 * @depends testImportMoreUnitPannesThanTotal
	 * @depends testImportNewMatosNoUnit
	 * @depends testImportNewMatosSomeUnits
	 * @covers ::importInventaire
	 */
	public function testImportUnitAfterInvalid() {
		// Préparation des données
		$file = 'SQL_backup_importUnitAfterInvalid.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		foreach(array(1, 5, 9, 12, 15, 20, 23, /* TODO (codes-barres) 26, */ 29, 32, 35, 38, 49, 54, 57, 60, 63, 66, 69, 72, 75, 78, 81) as $row) {
			$this->assertContains("WARNING ligne $row ignorée : ID = \"+\" mais la ligne précédente est invalide.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que seules les lignes valides ont été ajoutées dans la base de données
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUnitAfterInvalid::2',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test'),
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUnitAfterInvalid::4',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test'),
					array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUnitAfterInvalid::11',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test'),
					array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUnitAfterInvalid::26',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test'),
					array('id' => 8, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUnitAfterInvalid::29',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test'),
					array('id' => 9, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUnitAfterInvalid::34',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 5, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 6, 'quantite' => 3, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 7, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 8, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 9, 'quantite' => 3, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT')),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportUnitAfterInvalid::12',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Ligne valide avant prochain test'),
					array('id_matosunit' => 8, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportUnitAfterInvalid::28',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Ligne precedente invalide (quantite unitaire en panne superieure a 1)'),
					array('id_matosunit' => 9, 'id_matosdetail' => 8, 'ref' => 'SQL_Backup_Test::testImportUnitAfterInvalid::31',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Ligne precedente invalide (quantite unitaire en panne superieure a la quantite totale en panne)'),
					array('id_matosunit' => 10, 'id_matosdetail' => 9, 'ref' => 'SQL_Backup_Test::testImportUnitAfterInvalid::35',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Ligne valide avant prochain test'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant plus de lignes de nouveau matériel que la quantité disponible.
	 * 
	 * Cas de test :
	 * 1. Lignes unitaires pour un matériel existant (quantité inchangée) dont tout était déjà identifié unitairement
	 * 2. Plus de lignes unitaires que la quantité (inchangée) de leur matériel existant dont aucun n'était identifié unitairement
	 * 3. Plus de lignes unitaires que la quantité non identifiée unitairement de leur matériel existant partiellement identifié (quantité totale inchangée)
	 * 4. Trop de lignes unitaires pour un nouveau matériel en quantité égale à 1
	 * 5. Trop de lignes unitaires pour un nouveau matériel en quantité supérieure à 1
	 * 6. Plusieurs lignes unitaires en trop
	 * 7. Une seule ligne unitaire en trop
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @depends testImportNewMatosAllUnits
	 * @depends testImportUpdateMatosAddAllUnits
	 * @covers ::importInventaire
	 */
	public function testImportMoreUnitsThanQuantity() {
		// Préparation des données
		$file = 'SQL_backup_importMoreUnitsThanQuantity.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel remplissant les conditions de test pour les ajouts sur du matériel existant
		$unchangingDataSet = new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMoreUnitsThanQuantity::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Non identifie unitairement'),
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMoreUnitsThanQuantity::2',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Partiellement identifie unitairement')),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportMoreUnitsThanQuantity::3',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'))));
		$this->insertData($unchangingDataSet);
		$this->insertData(new ArrayDataSet(array(
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 5, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT')))));
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		foreach(array(2, 7, 12, 16, 21) as $row) {
			$this->assertContains("WARNING ligne $row ignorée : nombre de matériel identifié supérieur à la quantité totale.<br />", $this->getOutput());
		}
		foreach(array(3, 22) as $row) {
			$this->assertContains("WARNING ligne $row ignorée : ID = \"+\" mais la ligne précédente est invalide.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que seules les lignes valides ont été ajoutées dans la base de données,
		// et que les lignes intermédiaires dans robert_matos_generique ont été supprimées
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(),
				$unchangingDataSet, new ArrayDataSet(array(
					'robert_matos_detail' => array(
						array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMoreUnitsThanQuantity::11',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Remarque'),
						array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMoreUnitsThanQuantity::14',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Remarque')),
					'robert_matos_unit' => array(
						array('id_matosunit' => 8, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportMoreUnitsThanQuantity::6',
							'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
						array('id_matosunit' => 9, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportMoreUnitsThanQuantity::8',
							'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
						array('id_matosunit' => 10, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportMoreUnitsThanQuantity::9',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
						array('id_matosunit' => 11, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportMoreUnitsThanQuantity::12',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
						array('id_matosunit' => 12, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportMoreUnitsThanQuantity::15',
							'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
						array('id_matosunit' => 13, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportMoreUnitsThanQuantity::16',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes de nouveau matériel unitaire en erreur car la quantité en panne est invalide.
	 * 
	 * Cas de test :
	 * 1. Le 8e champ (quantité en panne) contient un nombre entier supérieur à 1
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @depends testImportNewMatosSomeUnits
	 * @covers ::importInventaire
	 */
	public function testImportInvalidUnitPanne() {
		// Préparation des données
		$file = 'SQL_backup_importInvalidUnitPanne.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		$this->assertStringStartsWith("WARNING ligne 2 ignorée : quantité en panne invalide.<br />", $this->getOutput());
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que seules les lignes valides ont été ajoutées dans la base de données,
		// y compris celle de matériel unitaire située après celle en erreur
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportInvalidUnitPanne::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant prochain test')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 2, 'panne' => 1, 'dateAchat' => null, 'ownerExt' => 'EXT'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant plus de lignes de nouveau matériel unitaire en panne que la quantité en panne indiquée dans la ligne de matériel générique correspondante.
	 * 
	 * Cas de test :
	 * 1. Ligne unitaire en panne pour un matériel existant non identifié qui a une quantité (inchangée) en panne nulle
	 * 2. Plus de lignes unitaires en panne, pour un matériel existant non identifié, que sa quantité totale (non nulle et inchangée) en panne
	 * 3. Ligne unitaire en panne pour un matériel existant dont toutes ses unités en panne (quantité inchangée) sont déjà identifiées
	 * 4. Plus de lignes unitaires en panne, pour un matériel existant dont ses unités en panne (quantité inchangée) sont partiellement identifiées, que sa quantité restante (non nulle) en panne non identifiée
	 * 5. Ligne unitaire en panne pour un nouveau matériel qui a une quantité en panne nulle
	 * 6. Plus de lignes unitaires en panne, pour un nouveau matériel, que sa quantité totale (non nulle) en panne
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @depends testImportNewMatosSomeUnits
	 * @depends testImportUpdateMatosAddSomeUnits
	 * @depends testImportUpdateMatosNotAllUnits
	 * @covers ::importInventaire
	 */
	public function testImportMoreUnitPannesThanTotal() {
		// Préparation des données
		$file = 'SQL_backup_importMoreUnitPannesThanTotal.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel remplissant les conditions de test pour les ajouts sur du matériel existant
		$unchangingDataSet = new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMoreUnitPannesThanTotal::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Non identifie unitairement et sans panne'),
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMoreUnitPannesThanTotal::2',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Non identifie unitairement et avec pannes'),
					array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMoreUnitPannesThanTotal::3',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Partiellement identifie unitairement et toutes pannes sur materiel identifiees'),
					array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMoreUnitPannesThanTotal::4',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Partiellement identifie unitairement et pannes partiellement sur materiel identifie')),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportMoreUnitPannesThanTotal::5',
						'panne' => 1, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 8, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportMoreUnitPannesThanTotal::6',
						'panne' => 1, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'))));
		$this->insertData($unchangingDataSet);
		$this->insertData(new ArrayDataSet(array(
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 5, 'quantite' => 3, 'panne' => 1, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 6, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 7, 'quantite' => 4, 'panne' => 2, 'dateAchat' => null, 'ownerExt' => 'EXT')))));
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		foreach(array(2, 6, 9, 14, 17, 21) as $row) {
			$this->assertContains("WARNING ligne $row ignorée : nombre de matériel identifié en panne supérieur à la quantité en panne totale.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que seules les lignes valides ont été ajoutées dans la base de données,
		// et que les quantités dans robert_matos_generique ont été mises à jour
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(),
				$unchangingDataSet, new ArrayDataSet(array(
					'robert_matos_detail' => array(
						array('id' => 8, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMoreUnitPannesThanTotal::14',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Remarque'),
						array('id' => 9, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMoreUnitPannesThanTotal::16',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Remarque')),
					'robert_matos_generique' => array(
						array('id_matosdetail' => 4, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 5, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 6, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 7, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 8, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 9, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT')),
					'robert_matos_unit' => array(
						array('id_matosunit' => 9, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportMoreUnitPannesThanTotal::8',
							'panne' => 1, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Panne valide'),
						array('id_matosunit' => 10, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportMoreUnitPannesThanTotal::11',
							'panne' => 1, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Panne valide'),
						array('id_matosunit' => 11, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportMoreUnitPannesThanTotal::12',
							'panne' => 1, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Panne valide'),
						array('id_matosunit' => 12, 'id_matosdetail' => 9, 'ref' => 'SQL_Backup_Test::testImportMoreUnitPannesThanTotal::17',
							'panne' => 1, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Panne valide'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes dont la quantité en panne est supérieure à la quantité totale.
	 * 
	 * Cas de test :
	 * 1. Modification de la quantité en panne d'un matériel existant non identifié unitairement avec une quantité supérieure à la quantité totale
	 * 2. Modification de la quantité en panne d'un matériel existant partiellement identifié unitairement avec une quantité inférieure à la quantité totale,
	 *    mais supérieure à la somme de la quantité non identifiée et de la quantité en panne identifiée
	 *    (de manière à ce que, sans compter le matériel identifié unitairement, la quantité en panne soit supéreure à la quantité)
	 * 3. Augmentation de la quantité en panne d'un matériel existant entièrement identifié unitairement
	 * 4. Ajout d'un nouveau matériel dont la quantité en panne est supérieure à la quantité totale
	 * 5. Ajout d'un nouveau matériel partiellement identifié unitairement dont la quantité en panne est inférieure à la quantité totale,
	 *    mais supérieure à la somme de la quantité non identifiée et de la quantité en panne identifiée
	 *    (de manière à ce que, sans compter le matériel identifié unitairement, la quantité en panne soit supéreure à la quantité)
	 * 
	 * Caractéristiques supplémentaires :
	 * * Quantités totales inchangées
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @depends testImportNewMatosNoUnits
	 * @depends testImportNewMatosSomeUnits
	 * @depends testImportUpdateMatosNotAllUnits
	 * @covers ::importInventaire
	 */
	public function testImportMorePannesThanQuantity() {
		// Préparation des données
		$file = 'SQL_backup_importMorePannesThanQuantity.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel existant
		$unchangingDataSet = new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMorePannesThanQuantity::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Quantite en panne superieure a la quantite totale'),
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMorePannesThanQuantity::2',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Quantite en panne superieure a la quantite non identifiee'),
					array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMorePannesThanQuantity::3',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Augmentation de la quantite en panne identifiee')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 5, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT')),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportMorePannesThanQuantity::4',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Non en panne'),
					array('id_matosunit' => 8, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportMorePannesThanQuantity::5',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Non en panne'),
					array('id_matosunit' => 9, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportMorePannesThanQuantity::6',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Non en panne'))));
		$this->insertData($unchangingDataSet);
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		foreach(array(1, 3, 5, 7, 11) as $row) {
			$this->assertContains("WARNING ligne $row ignorée : quantité en panne invalide.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(),
				$unchangingDataSet, new ArrayDataSet(array(
					'robert_matos_detail' => array(
						array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportMorePannesThanQuantity::8',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Quantite en panne superieure a la quantite non identifiee')),
					'robert_matos_generique' => array(
						array('id_matosdetail' => 6, 'quantite' => 2, 'panne' => 2, 'dateAchat' => '2016-05-31', 'ownerExt' => null)),
					'robert_matos_unit' => array(
						array('id_matosunit' => 10, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportMorePannesThanQuantity::9',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Non en panne et valide'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes de nouveau matériel où tout est identifié unitairement.
	 * 
	 * Cas de test :
	 * 1. Matériel avec une seule unité
	 * 2. Matériel avec plusieurs unités
	 * 3. Ligne de matériel unitaire avec les champs ignorés non vides
	 * 4. Ligne de matériel unitaire interne pour un matériel générique interne
	 * 5. Ligne de matériel unitaire externe pour un matériel générique interne
	 * 6. Ligne de matériel unitaire externe pour un matériel générique externe
	 * 7. Ligne de matériel unitaire interne pour un matériel générique externe
	 * 8. Matériel avec unités en panne
	 * 
	 * Caractéristiques supplémentaires :
	 * * Valeurs différentes entre tous les cas de test
	 * * Tous les champs, même facultatifs, sont renseignés (sauf champs incompatibles entre eux)
	 * * Sous-catégories existantes (pas de création)
	 * 
	 * @test
	 * @depends testImportNewMatosSomeUnits
	 * @covers ::importInventaire
	 */
	public function testImportNewMatosAllUnits() {
		// Préparation des données
		$file = 'SQL_backup_importNewMatosAllUnits.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation 1', 'ref' => 'SQL_Backup_Test::testImportNewMatosAllUnits::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant test 1'),
					array('id' => 5, 'label' => 'Designation 2', 'ref' => 'SQL_Backup_Test::testImportNewMatosAllUnits::3',
						'categorie' => 'lumiere', 'sousCateg' => 16, 'tarifLoc' => 3.0, 'valRemp' => 4.0, 'remarque' => 'Ligne valide avant test 2'),
					array('id' => 6, 'label' => 'Designation 3', 'ref' => 'SQL_Backup_Test::testImportNewMatosAllUnits::6',
						'categorie' => 'transport', 'sousCateg' => 21, 'tarifLoc' => 5.0, 'valRemp' => 6.0, 'remarque' => 'Ligne valide avant test 3'),
					array('id' => 7, 'label' => 'Designation 4', 'ref' => 'SQL_Backup_Test::testImportNewMatosAllUnits::9',
						'categorie' => 'structure', 'sousCateg' => 10, 'tarifLoc' => 7.0, 'valRemp' => 8.0, 'remarque' => 'Ligne valide avant test 4')),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportNewMatosAllUnits::2',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Champs ignores non vides'),
					array('id_matosunit' => 8, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportNewMatosAllUnits::4',
						'panne' => 0, 'dateAchat' => '2016-05-30', 'ownerExt' => null, 'remarque' => 'Materiel interne'),
					array('id_matosunit' => 9, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportNewMatosAllUnits::5',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT1', 'remarque' => 'Materiel externe mais type interne'),
					array('id_matosunit' => 10, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportNewMatosAllUnits::7',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT3', 'remarque' => 'Materiel externe'),
					array('id_matosunit' => 11, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportNewMatosAllUnits::8',
						'panne' => 0, 'dateAchat' => '2016-05-29', 'ownerExt' => null, 'remarque' => 'Materiel interne mais type externe'),
					array('id_matosunit' => 12, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportNewMatosAllUnits::10',
						'panne' => 1, 'dateAchat' => '2016-05-28', 'ownerExt' => null, 'remarque' => 'Panne'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes de nouveau matériel où une partie seulement est identifiée unitairement.
	 * 
	 * Cas de test :
	 * 1. Matériel avec une seule unité identifiée
	 * 2. Matériel avec plusieurs unités identifiées
	 * 3. Matériel avec toutes les unités identifiées sauf une
	 * 4. Ligne de matériel unitaire avec les champs ignorés non vides
	 * 5. Ligne de matériel unitaire interne pour un matériel générique interne
	 * 6. Ligne de matériel unitaire externe pour un matériel générique interne
	 * 7. Ligne de matériel unitaire externe pour un matériel générique externe
	 * 8. Ligne de matériel unitaire interne pour un matériel générique externe
	 * 9. Matériel avec unités en panne (toutes identifiées)
	 * 10. Matériel avec unités en panne (partiellement identifiées)
	 * 
	 * Caractéristiques supplémentaires :
	 * * Valeurs différentes entre tous les cas de test
	 * * Tous les champs, même facultatifs, sont renseignés (sauf champs incompatibles entre eux)
	 * * Sous-catégories existantes (pas de création)
	 * 
	 * @test
	 * @depends testImportNewMatosNoUnit
	 * @covers ::importInventaire
	 */
	public function testImportNewMatosSomeUnits() {
		// Préparation des données
		$file = 'SQL_backup_importNewMatosSomeUnits.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation 1', 'ref' => 'SQL_Backup_Test::testImportNewMatosSomeUnits::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ligne valide avant test 1'),
					array('id' => 5, 'label' => 'Designation 2', 'ref' => 'SQL_Backup_Test::testImportNewMatosSomeUnits::3',
						'categorie' => 'lumiere', 'sousCateg' => 16, 'tarifLoc' => 3.0, 'valRemp' => 4.0, 'remarque' => 'Ligne valide avant test 2'),
					array('id' => 6, 'label' => 'Designation 3', 'ref' => 'SQL_Backup_Test::testImportNewMatosSomeUnits::6',
						'categorie' => 'transport', 'sousCateg' => 21, 'tarifLoc' => 5.0, 'valRemp' => 6.0, 'remarque' => 'Ligne valide avant test 3'),
					array('id' => 7, 'label' => 'Designation 4', 'ref' => 'SQL_Backup_Test::testImportNewMatosSomeUnits::9',
						'categorie' => 'structure', 'sousCateg' => 10, 'tarifLoc' => 7.0, 'valRemp' => 8.0, 'remarque' => 'Ligne valide avant test 4'),
					array('id' => 8, 'label' => 'Designation 5', 'ref' => 'SQL_Backup_Test::testImportNewMatosSomeUnits::11',
						'categorie' => 'son', 'sousCateg' => 2, 'tarifLoc' => 9.0, 'valRemp' => 10.0, 'remarque' => 'Ligne valide avant test 5')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 5, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-28', 'ownerExt' => null),
					array('id_matosdetail' => 6, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT2'),
					array('id_matosdetail' => 7, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-25', 'ownerExt' => null),
					array('id_matosdetail' => 8, 'quantite' => 2, 'panne' => 1, 'dateAchat' => '2016-05-23', 'ownerExt' => null)),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportNewMatosSomeUnits::2',
						'panne' => 0, 'dateAchat' => '2016-05-30', 'ownerExt' => null, 'remarque' => 'Champs ignores non vides'),
					array('id_matosunit' => 8, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportNewMatosSomeUnits::4',
						'panne' => 0, 'dateAchat' => '2016-05-27', 'ownerExt' => null, 'remarque' => 'Materiel interne'),
					array('id_matosunit' => 9, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportNewMatosSomeUnits::5',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT1', 'remarque' => 'Materiel externe mais type interne'),
					array('id_matosunit' => 10, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportNewMatosSomeUnits::7',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT3', 'remarque' => 'Materiel externe'),
					array('id_matosunit' => 11, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportNewMatosSomeUnits::8',
						'panne' => 0, 'dateAchat' => '2016-05-26', 'ownerExt' => null, 'remarque' => 'Materiel interne mais type externe'),
					array('id_matosunit' => 12, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportNewMatosSomeUnits::10',
						'panne' => 1, 'dateAchat' => '2016-05-24', 'ownerExt' => null, 'remarque' => 'Panne'),
					array('id_matosunit' => 13, 'id_matosdetail' => 8, 'ref' => 'SQL_Backup_Test::testImportNewMatosSomeUnits::12',
						'panne' => 1, 'dateAchat' => '2016-05-22', 'ownerExt' => null, 'remarque' => 'Panne'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes en erreur car la désignation est invalide.
	 * 
	 * Cas de test :
	 * 1. Le 4e champ est vide
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de lignes d'ajout et de modification de matériel
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportInvalidLabel() {
		// Préparation des données
		$file = 'SQL_backup_importInvalidLabel.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		$lineCount = count(file(__DIR__.DIRECTORY_SEPARATOR.$file, FILE_SKIP_EMPTY_LINES));
		for($row = 1; $row <= $lineCount; $row++) {
			$this->assertContains("WARNING ligne $row ignorée : désignation manquante.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes en erreur car la catégorie est invalide.
	 * 
	 * Cas de test (sur le 5e champ) :
	 * 1. Champ vide
	 * 2. Catégorie inexistante et aucune ressemblance avec une catégorie existante
	 * 3. Catégorie inexistante mais qui commence par le nom d'une catégorie existante
	 * 4. Catégorie inexistante mais qui se termine par le nom d'une catégorie existante
	 * 5. Catégorie inexistante mais dont le nom contient celui d'une catégorie existante
	 * 6. Catégorie inexistante mais dont le nom correspond à la version accentuée du nom d'une catégorie existante
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de lignes d'ajout et de modification de matériel
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportInvalidCategorie() {
		// Préparation des données
		$file = 'SQL_backup_importInvalidCategorie.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		$lineCount = count(file(__DIR__.DIRECTORY_SEPARATOR.$file, FILE_SKIP_EMPTY_LINES));
		for($row = 1; $row <= $lineCount; $row++) {
			$this->assertContains("WARNING ligne $row ignorée : catégorie invalide.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes en erreur car la quantité est invalide.
	 * 
	 * Cas de test (sur le 7e champ) :
	 * 1. Nombre négatif
	 * 2. Zéro (nombre positif mais inférieur à 1)
	 * 3. Chaîne commençant par un chiffre
	 * 4. Nombre décimal
	 * 5. Nombre avec séparateur de milliers
	 * 6. Caractère Unicode non-ASCII représentant un chiffre
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de lignes d'ajout et de modification de matériel
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportInvalidQuantite() {
		// Préparation des données
		$file = 'SQL_backup_importInvalidQuantite.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		$lineCount = count(file(__DIR__.DIRECTORY_SEPARATOR.$file, FILE_SKIP_EMPTY_LINES));
		for($row = 1; $row <= $lineCount; $row++) {
			$this->assertContains("WARNING ligne $row ignorée : quantité invalide.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes en erreur car le tarif de location est invalide.
	 * 
	 * Cas de test (sur le 9e champ) :
	 * 1. Nombre négatif
	 * 2. Chaîne commençant par un chiffre
	 * 3. Nombre avec séparateur de milliers
	 * 4. Nombre avec plusieurs séparateurs décimaux
	 * 5. Caractère Unicode non-ASCII représentant un chiffre
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de lignes d'ajout et de modification de matériel
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportInvalidTarif() {
		// Préparation des données
		$file = 'SQL_backup_importInvalidTarif.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		$lineCount = count(file(__DIR__.DIRECTORY_SEPARATOR.$file, FILE_SKIP_EMPTY_LINES));
		for($row = 1; $row <= $lineCount; $row++) {
			$this->assertContains("WARNING ligne $row ignorée : tarif de location invalide.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes en erreur car la valeur de remplacement est invalide.
	 * 
	 * Cas de test (sur le 10e champ) :
	 * 1. Nombre négatif
	 * 2. Chaîne commençant par un chiffre
	 * 3. Nombre avec séparateur de milliers
	 * 4. Nombre avec plusieurs séparateurs décimaux
	 * 5. Caractère Unicode non-ASCII représentant un chiffre
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de lignes d'ajout et de modification de matériel
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportInvalidValeur() {
		// Préparation des données
		$file = 'SQL_backup_importInvalidValeur.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		$lineCount = count(file(__DIR__.DIRECTORY_SEPARATOR.$file, FILE_SKIP_EMPTY_LINES));
		for($row = 1; $row <= $lineCount; $row++) {
			$this->assertContains("WARNING ligne $row ignorée : valeur de remplacement invalide.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes dont la sous-catégorie n'existe pas.
	 * 
	 * Cas de test (sur le 6e champ) :
	 * 1. Sous-catégorie inexistante sans caractères spéciaux
	 * 2. Sous-catégorie inexistante avec caractères spéciaux
	 * 3. Réutilisation d'une sous-catégorie créée dans une ligne précédente (séparée d'au moins une ligne utilisant une autre sous-catégorie)
	 * 4. Lignes de modification de matériel existant
	 * 5. Lignes d'ajout de nouveau matériel
	 * 
	 * Caractéristiques supplémentaires :
	 * * Création de sous-catégories dans un maximum de catégories différentes
	 * * Alternance de matériel interne et externe
	 * * Numéro d'ordre de sous-catégorie le plus élevé différent du dernier id de sous-catégorie
	 * 
	 * @test
	 * @depends testImportNewMatosNoUnit
	 * @depends testImportUpdateMatosNotAllUnits
	 * @covers ::importInventaire
	 */
	public function testImportNewSousCategorie() {
		// Préparation des données
		$file = 'SQL_backup_importNewSousCategorie.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel remplissant les conditions de test pour les modifications de matériel existant
		// et ajout d'une sous-catégorie pour que le numéro d'ordre des sous-catégories créées soit différent de leur id
		$unchangingDataSet = new ArrayDataSet(array(
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 5, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT')),
				'robert_matos_sous_cat' => array(
					array('id' => 23, 'label' => 'SOUS-CATEGORIE', 'ordre' => 30))));
		$this->insertData($unchangingDataSet);
		$this->insertData(new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportNewSousCategorie::1',
						'categorie' => 'transport', 'sousCateg' => 24, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Nouvelle sous-categorie'),
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportNewSousCategorie::2',
						'categorie' => 'son', 'sousCateg' => 8, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Reutilisation d'une nouvelle sous-categorie (apres une ligne utilisant une sous-categorie existante)")))));
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(),
				$unchangingDataSet, new ArrayDataSet(array(
					'robert_matos_detail' => array(
						array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportNewSousCategorie::1',
							'categorie' => 'son', 'sousCateg' => 24, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Nouvelle sous-categorie'),
						array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportNewSousCategorie::2',
							'categorie' => 'son', 'sousCateg' => 24, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Reutilisation d'une nouvelle sous-categorie (apres une ligne utilisant une sous-categorie existante)"),
						array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportNewSousCategorie::3',
							'categorie' => 'transport', 'sousCateg' => 25, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Nouvelle sous-categorie'),
						array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportNewSousCategorie::4',
							'categorie' => 'structure', 'sousCateg' => 26, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Nouvelle sous-categorie avec caracteres speciaux'),
						array('id' => 8, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportNewSousCategorie::5',
							'categorie' => 'son', 'sousCateg' => 24, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Reutilisation d'une nouvelle sous-categorie (apres une ligne utilisant une sous-categorie existante)")),
					'robert_matos_generique' => array(
						array('id_matosdetail' => 6, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 7, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 8, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null)),
					'robert_matos_sous_cat' => array(
						array('id' => 24, 'label' => 'Instruments', 'ordre' => 31),
						array('id' => 25, 'label' => 'Flight cases', 'ordre' => 32),
						array('id' => 26, 'label' => 'Supports vidéoprojecteur !', 'ordre' => 33))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes de nouveau matériel où aucun n'est identifié unitairement.
	 * 
	 * Cas de test :
	 * 1. Cas nominal (pas de panne, tarif de location et valeur de remplacements entiers et supérieurs à 0, matériel interne)
	 * 2. Tarif de location non entier
	 * 3. Tarif de location nul
	 * 4. Valeur de remplacement non entière
	 * 5. Valeur de remplacement nulle
	 * 6. Matériel externe
	 * 7. Quantité en panne non nulle
	 * 
	 * Caractéristiques supplémentaires :
	 * * Valeurs différentes entre tous les cas de test
	 * * Tous les champs, même facultatifs, sont renseignés (sauf champs incompatibles entre eux)
	 * * Création de matériel dans toutes les catégories
	 *
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportNewMatosNoUnit() {
		// Préparation des données
		$file = 'SQL_backup_importNewMatosNoUnit.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation 1', 'ref' => 'SQL_Backup_Test::testImportNewMatosNoUnit::1', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'Qtotale' => 1, 'tarifLoc' => 2.0, 'valRemp' => 3.0,
						'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Cas nominal'),
					array('id' => 5, 'label' => 'Designation 2', 'ref' => 'SQL_Backup_Test::testImportNewMatosNoUnit::2', 'panne' => 0,
						'categorie' => 'lumiere', 'sousCateg' => 16, 'Qtotale' => 10, 'tarifLoc' => 2.5, 'valRemp' => 19.99,
						'dateAchat' => '2016-05-30', 'ownerExt' => null, 'remarque' => 'Valeur et tarif non entiers'),
					array('id' => 6, 'label' => 'Designation 3', 'ref' => 'SQL_Backup_Test::testImportNewMatosNoUnit::3', 'panne' => 0,
						'categorie' => 'structure', 'sousCateg' => 10, 'Qtotale' => 4, 'tarifLoc' => 0.0, 'valRemp' => 0.0,
						'dateAchat' => '2016-05-29', 'ownerExt' => null, 'remarque' => 'Valeur et tarif nuls'),
					array('id' => 7, 'label' => 'Designation 4', 'ref' => 'SQL_Backup_Test::testImportNewMatosNoUnit::4', 'panne' => 0,
						'categorie' => 'transport', 'sousCateg' => 21, 'Qtotale' => 5, 'tarifLoc' => 6.0, 'valRemp' => 7.0,
						'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Materiel externe'),
					array('id' => 8, 'label' => 'Designation 5', 'ref' => 'SQL_Backup_Test::testImportNewMatosNoUnit::5', 'panne' => 8,
						'categorie' => 'son', 'sousCateg' => 2, 'Qtotale' => 20, 'tarifLoc' => 15.0, 'valRemp' => 30.0,
						'dateAchat' => '2016-05-28', 'ownerExt' => null, 'remarque' => 'Pannes'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes de modification de matériel en erreur car l'id du matériel n'existe pas.
	 * 
	 * Cas de test :
	 * 1. Les 1er champ contient un nombre ne correspondant à aucun id de matériel existant
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportInvalidId() {
		// Préparation des données
		$file = 'SQL_backup_importInvalidId.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		$lineCount = count(file(__DIR__.DIRECTORY_SEPARATOR.$file, FILE_SKIP_EMPTY_LINES));
		for($row = 1; $row <= $lineCount; $row++) {
			$this->assertContains("WARNING ligne $row ignorée : id ne correspondant à aucun matériel existant.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($this->getDataSet()->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes de modification de matériel existant dont la quantité totale indiquée est inférieure à la quantité déjà identifiée unitairement.
	 * 
	 * Cas de test :
	 * 1. Nouvelle quantité inférieure de 1 à la quantité identifiée unitairement
	 * 2. Nouvelle quantité inférieure de plusieurs à la quantité identifiée unitairement
	 * 3. Nouvelle quantité inférieure à la quantité identifiée unitairement, mais supérieure ou égale à la quantité non identifiée
	 * 4. Nouvelle quantité inférieure à la quantité identifiée unitairement et à la quantité non identifiée
	 * 5. Modification d'un matériel existant dont tout était déjà identifié unitairement
	 * 6. Modification d'un matériel existant partiellement identifié unitairement
	 * 
	 * Caractéristiques supplémentaires :
	 * * Quantités en panne inchangées
	 * * Nouvelles quantités supérieures ou égales aux quantités en panne du matériel correspondant
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @depends testImportUpdateMatosAllUnits
	 * @depends testImportUpdateMatosNotAllUnits
	 * @covers ::importInventaire
	 */
	public function testImportLessQuantityThanUnits() {
		// Préparation des données
		$file = 'SQL_backup_importLessQuantityThanUnits.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel remplissant les conditions de test
		$additionalDataSet = new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportLessQuantityThanUnits::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Nouvelle quantite inferieure a la quantite identifiee unitairement mais superieure ou egale a la quantite non identifiee')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null)),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportLessQuantityThanUnits::2',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 8, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportLessQuantityThanUnits::3',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'))));
		$this->insertData($additionalDataSet);
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		$lineCount = count(file(__DIR__.DIRECTORY_SEPARATOR.$file, FILE_SKIP_EMPTY_LINES));
		for($row = 1; $row <= $lineCount; $row++) {
			$this->assertContains("WARNING ligne $row ignorée : quantité inférieure au nombre de matériel identifié individuellement déjà présent en base.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), $additionalDataSet));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes de modification de matériel existant dont la quantité en panne indiquée est inférieure à la quantité en panne déjà identifiée unitairement.
	 * 
	 * Cas de test :
	 * 1. Nouvelle quantité en panne inférieure de 1 à la quantité en panne identifiée unitairement
	 * 2. Nouvelle quantité en panne inférieure de plusieurs à la quantité en panne identifiée unitairement
	 * 3. Nouvelle quantité en panne inférieure à la quantité en panne identifiée unitairement, mais supérieure ou égale à la quantité en panne non identifiée
	 * 4. Nouvelle quantité en panne inférieure à la quantité en panne identifiée unitairement et à la quantité en panne non identifiée
	 * 5. Modification d'un matériel existant dont tout était déjà identifié unitairement
	 * 6. Modification d'un matériel existant partiellement identifié unitairement
	 * 
	 * Caractéristiques supplémentaires :
	 * * Quantités totales inchangées
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @depends testImportUpdateMatosAllUnits
	 * @depends testImportUpdateMatosNotAllUnits
	 * @covers ::importInventaire
	 */
	public function testImportLessPannesThanUnits() {
		// Préparation des données
		$file = 'SQL_backup_importLessPannesThanUnits.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel remplissant les conditions de test
		$additionalDataSet = new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportLessPannesThanUnits::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Materiel en panne entierement identifie unitairement'),
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportLessPannesThanUnits::2',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Materiel en panne avec moins de la moitie identifie unitairement')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 5, 'quantite' => 1, 'panne' => 1, 'dateAchat' => null, 'ownerExt' => 'EXT')),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportLessQuantityThanUnits::3',
						'panne' => 1, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Quantite en panne = 1'),
					array('id_matosunit' => 8, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportLessQuantityThanUnits::4',
						'panne' => 1, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Quantite en panne = 2'),
					array('id_matosunit' => 9, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportLessQuantityThanUnits::5',
						'panne' => 1, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Quantite en panne = 2'))));
		$this->insertData($additionalDataSet);
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		$lineCount = count(file(__DIR__.DIRECTORY_SEPARATOR.$file, FILE_SKIP_EMPTY_LINES));
		for($row = 1; $row <= $lineCount; $row++) {
			$this->assertContains("WARNING ligne $row ignorée : quantité en panne inférieure au nombre de matériel identifié individuellement en panne déjà présent en base.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification que le contenu de la base de données n'a pas changé
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), $additionalDataSet));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes de modification de matériel existant sans changer la quantité et dont tout est identifié unitairement.
	 * 
	 * Cas de test :
	 * 1. Modification de chacun des champs individuellement (sauf id, quantité totale et quantité en panne)
	 * 2. Modification de tous les champs dans une même ligne (sauf id, quantité totale et quantité en panne)
	 * 3. Modification avec un tarif de location non entier
	 * 4. Modification avec un tarif de location nul
	 * 5. Modification avec une valeur de remplacement non entière
	 * 6. Modification avec une valeur de remplacement nulle
	 * 7. Changement d'un matériel interne en externe
	 * 8. Changement d'un matériel externe en interne
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de matériel interne et externe
	 *
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportUpdateMatosAllUnits() {
		// Préparation des données
		$file = 'SQL_backup_importUpdateMatosAllUnits.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel remplissant les conditions de test
		$unchangingDataSet = new ArrayDataSet(array(
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::16',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 8, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::17',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 9, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::18',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 10, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::19',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 11, 'id_matosdetail' => 8, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::20',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 12, 'id_matosdetail' => 9, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::21',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 13, 'id_matosdetail' => 10, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::22',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 14, 'id_matosdetail' => 11, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::23',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 15, 'id_matosdetail' => 12, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::24',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 16, 'id_matosdetail' => 13, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::25',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 17, 'id_matosdetail' => 14, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::26',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 18, 'id_matosdetail' => 15, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::27',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 19, 'id_matosdetail' => 16, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::28',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 20, 'id_matosdetail' => 17, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::29',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 21, 'id_matosdetail' => 18, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::30',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'))));
		$this->insertData($unchangingDataSet);
		$this->insertData(new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::1', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Modification du code-barres'), //TODO revoir codes-barres
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::2', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Modification de la reference'),
					array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::3', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Modification de la designation'),
					array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::4', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Modification de la categorie'),
					array('id' => 8, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::5', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Modification de la sous-categorie'),
					array('id' => 9, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::6', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Modification du tarif de location'),
					array('id' => 10, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::7', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Modification du tarif de location'),
					array('id' => 11, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::8', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Modification de la valeur de remplacement'),
					array('id' => 12, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::9', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Modification de la valeur de remplacement'),
					array('id' => 13, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::10', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Modification de la remarque'),
					array('id' => 14, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::11', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => "Modification de la date d'achat"),
					array('id' => 15, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::12', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Modification du prestataire externe'),
					array('id' => 16, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::13', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Modification de tous les champs'),
					array('id' => 17, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::14', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => "Changement d'interne en externe"),
					array('id' => 18, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::15', 'panne' => 0,
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
						'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => "Changement d'externe en interne")))));
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(),
				$unchangingDataSet, new ArrayDataSet(array(
					'robert_matos_detail' => array(
						array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::1', 'panne' => 0,
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
							'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Modification du code-barres'), //TODO revoir codes-barres
						array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::2_NEW', 'panne' => 0,
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
							'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Modification de la reference'),
						array('id' => 6, 'label' => 'Designation_NEW', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::3', 'panne' => 0,
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
							'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Modification de la designation'),
						array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::4', 'panne' => 0,
							'categorie' => 'lumiere', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
							'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Modification de la categorie'),
						array('id' => 8, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::5', 'panne' => 0,
							'categorie' => 'son', 'sousCateg' => 2, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
							'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Modification de la sous-categorie'),
						array('id' => 9, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::6', 'panne' => 0,
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.5, 'valRemp' => 2.0,
							'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Modification du tarif de location'),
						array('id' => 10, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::7', 'panne' => 0,
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 0.0, 'valRemp' => 2.0,
							'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Modification du tarif de location'),
						array('id' => 11, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::8', 'panne' => 0,
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.5,
							'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Modification de la valeur de remplacement'),
						array('id' => 12, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::9', 'panne' => 0,
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 0.0,
							'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Modification de la valeur de remplacement'),
						array('id' => 13, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::10', 'panne' => 0,
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
							'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Modification de la remarque_NEW'),
						array('id' => 14, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::11', 'panne' => 0,
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
							'dateAchat' => '2015-01-01', 'ownerExt' => null, 'remarque' => "Modification de la date d'achat"),
						array('id' => 15, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::12', 'panne' => 0,
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
							'dateAchat' => null, 'ownerExt' => 'EXT_NEW', 'remarque' => 'Modification du prestataire externe'),
						array('id' => 16, 'label' => 'Designation_NEW', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::13_NEW', 'panne' => 0,
							'categorie' => 'lumiere', 'sousCateg' => 2, 'tarifLoc' => 2.0, 'valRemp' => 3.0,
							'dateAchat' => '2015-01-01', 'ownerExt' => null, 'remarque' => 'Modification de tous les champs_NEW'),
						array('id' => 17, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::14', 'panne' => 0,
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
							'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => "Changement d'interne en externe"),
						array('id' => 18, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAllUnits::15', 'panne' => 0,
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0,
							'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => "Changement d'externe en interne"))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes de modification de matériel existant sans changer la quantité et dont tout n'est pas identifié unitairement.
	 * 
	 * Cas de test :
	 * 1. Modification de chacun des champs individuellement (sauf id et quantité totale)
	 * 2. Modification de tous les champs dans une même ligne (sauf id et quantité totale)
	 * 3. Augmentation de la quantité en panne pour un matériel qui n'est pas identifié unitairement
	 * 4. Augmentation de la quantité en panne pour un matériel dont une partie est identifiée unitairement
	 * 5. Diminution de la quantité en panne pour correspondre seulement à la quantité en panne parmi le matériel identifiée unitairement
	 * 6. Modification avec un tarif de location non entier
	 * 7. Modification avec un tarif de location nul
	 * 8. Modification avec une valeur de remplacement non entière
	 * 9. Modification avec une valeur de remplacement nulle
	 * 10. Changement d'un matériel interne en externe
	 * 11. Changement d'un matériel externe en interne
	 * 
	 * Caractéristiques supplémentaires :
	 * * Utilisation principalement de matériel ayant au moins une partie identifiée unitairement
	 * * Alternance de matériel interne et externe
	 *
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportUpdateMatosNotAllUnits() {
		// Préparation des données
		$file = 'SQL_backup_importUpdateMatosNotAllUnits.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel remplissant les conditions de test
		$unchangingDataSet = new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 9, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::6',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Augmentation de la quantite en panne'),
					array('id' => 10, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::7',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Augmentation de la quantite en panne'),
					array('id' => 11, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::8',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Diminution de la quantite en panne'),
					array('id' => 17, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::14',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Modification de la date d'achat"),
					array('id' => 18, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::15',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification du prestataire externe'),
					array('id' => 20, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::17',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Changement d'interne en externe"),
					array('id' => 21, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::18',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Changement d'externe en interne")),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 5, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 6, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 7, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 8, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 12, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 13, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 14, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 15, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 16, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null)),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::19',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 8, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::20',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 9, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::21',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 10, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::22',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 11, 'id_matosdetail' => 8, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::23',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 12, 'id_matosdetail' => 10, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::24',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 13, 'id_matosdetail' => 11, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::25',
						'panne' => 1, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 14, 'id_matosdetail' => 12, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::26',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 15, 'id_matosdetail' => 13, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::27',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 16, 'id_matosdetail' => 14, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::28',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 17, 'id_matosdetail' => 15, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::29',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 18, 'id_matosdetail' => 16, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::30',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 19, 'id_matosdetail' => 17, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::31',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 20, 'id_matosdetail' => 18, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::32',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 21, 'id_matosdetail' => 19, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::33',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 22, 'id_matosdetail' => 20, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::34',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 23, 'id_matosdetail' => 21, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::35',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'))));
		$this->insertData($unchangingDataSet);
		$this->insertData(new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification du code-barres'), //TODO revoir codes-barres
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::2',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de la reference'),
					array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::3',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de la designation'),
					array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::4',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de la categorie'),
					array('id' => 8, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::5',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de la sous-categorie'),
					array('id' => 12, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::9',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification du tarif de location'),
					array('id' => 13, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::10',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification du tarif de location'),
					array('id' => 14, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::11',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de la valeur de remplacement'),
					array('id' => 15, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::12',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de la valeur de remplacement'),
					array('id' => 16, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::13',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de la remarque'),
					array('id' => 19, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::16',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de tous les champs')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 9, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 10, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 11, 'quantite' => 1, 'panne' => 1, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 17, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 18, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 19, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 20, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 21, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT')))));
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(),
				$unchangingDataSet, new ArrayDataSet(array(
					'robert_matos_detail' => array(
						array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::1',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification du code-barres'),
						array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::2_NEW',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de la reference'),
						array('id' => 6, 'label' => 'Designation_NEW', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::3',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de la designation'),
						array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::4',
							'categorie' => 'lumiere', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de la categorie'),
						array('id' => 8, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::5',
							'categorie' => 'son', 'sousCateg' => 2, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de la sous-categorie'),
						array('id' => 12, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::9',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.5, 'valRemp' => 2.0, 'remarque' => 'Modification du tarif de location'),
						array('id' => 13, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::10',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 0.0, 'valRemp' => 2.0, 'remarque' => 'Modification du tarif de location'),
						array('id' => 14, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::11',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.5, 'remarque' => 'Modification de la valeur de remplacement'),
						array('id' => 15, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::12',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 0.0, 'remarque' => 'Modification de la valeur de remplacement'),
						array('id' => 16, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::13',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Modification de la remarque_NEW'),
						array('id' => 19, 'label' => 'Designation_NEW', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosNotAllUnits::16_NEW',
							'categorie' => 'lumiere', 'sousCateg' => 2, 'tarifLoc' => 2.0, 'valRemp' => 3.0, 'remarque' => 'Modification de tous les champs_NEW')),
					'robert_matos_generique' => array(
						array('id_matosdetail' => 9, 'quantite' => 2, 'panne' => 1, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 10, 'quantite' => 1, 'panne' => 1, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 11, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 17, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2015-01-01', 'ownerExt' => null),
						array('id_matosdetail' => 18, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT_NEW'),
						array('id_matosdetail' => 19, 'quantite' => 1, 'panne' => 1, 'dateAchat' => '2015-01-01', 'ownerExt' => null),
						array('id_matosdetail' => 20, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 21, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes de modification de matériel existant en diminuant la quantité totale avec la quantité identifiée unitairement (de manière à supprimer le matériel non identifié).
	 * 
	 * Cas de test :
	 * 1. Diminution de la quantité totale de 1, sans changer la quantité identifiée unitairement
	 * 2. Diminution de la quantité totale de plusieurs, sans changer la quantité identifiée unitairement
	 * 3. Diminution de la quantité totale après avoir ajouté du matériel identifié unitairement
	 * 4. Diminution de la quantité totale avec une valeur inférieure à la quantité en panne sans modifier cette dernière
	 * 
	 * Caractéristiques supplémentaires :
	 * * La nouvelle quantité totale est toujours égale à la quantité identifiée unitairement
	 * * Alternance de matériel interne et externe
	 *
	 * @test
	 * @depends testImportUpdateMatosAddSomeUnits
	 * @covers ::importInventaire
	 */
	public function testImportUpdateMatosDelNonUnits() {
		// Préparation des données
		$file = 'SQL_backup_importUpdateMatosDelNonUnits.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel remplissant les conditions de test
		$unchangingDataSet = new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosDelNonUnits::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Un materiel non identifie'),
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosDelNonUnits::2',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Plusieurs materiels non identifies'),
					array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosDelNonUnits::3',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ajout de materiel identifie'),
					array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosDelNonUnits::4',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Diminution sous la quantite en panne')),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosDelNonUnits::5',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 8, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosDelNonUnits::6',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 9, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosDelNonUnits::7',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 10, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosDelNonUnits::8',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'))));
		$this->insertData($unchangingDataSet);
		$this->insertData(new ArrayDataSet(array(
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 5, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 6, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 7, 'quantite' => 2, 'panne' => 2, 'dateAchat' => null, 'ownerExt' => 'EXT')))));
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données et notamment que les lignes dans robert_matos_generique ont été supprimées
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(),
				$unchangingDataSet, new ArrayDataSet(array(
					'robert_matos_unit' => array(
						array('id_matosunit' => 11, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosDelNonUnits::9',
							'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes de modification de matériel existant en augmentant la quantité totale sans ajouter de nouveau matériel identifié unitairement.
	 * 
	 * Cas de test :
	 * 1. Augmentation de la quantité totale de 1
	 * 2. Augmentation de la quantité totale de plusieurs
	 * 3. Augmentation de la quantité totale pour du matériel dont seulement une partie était identifiée unitairement
	 * 4. Augmentation de la quantité totale pour du matériel qui était entièrement identifié unitairement
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de matériel interne et externe
	 *
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportUpdateMatosAddNonUnits() {
		// Préparation des données
		$file = 'SQL_backup_importUpdateMatosAddNonUnits.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel remplissant les conditions de test
		$unchangingDataSet = new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddNonUnits::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Materiel partiellement identifie unitairement'),
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddNonUnits::2',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Materiel totalement identifie unitairement')),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddNonUnits::3',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 8, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddNonUnits::4',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 9, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddNonUnits::5',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'))));
		$this->insertData($unchangingDataSet);
		$this->insertData(new ArrayDataSet(array(
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null)))));
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données et notamment que les lignes dans robert_matos_generique ont été ajoutées ou modifiées
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(),
				$unchangingDataSet, new ArrayDataSet(array(
					'robert_matos_generique' => array(
						array('id_matosdetail' => 4, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 5, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes d'ajout de matériel unitaire à du matériel existant de manière à ce que tout le matériel soit dorénavant identifié.
	 * 
	 * Cas de test :
	 * 1. Ajout d'un seul matériel unitaire
	 * 2. Ajout de plusieurs matériels unitaires
	 * 3. Ajout de matériel unitaire sans changer la quantité totale
	 * 4. Ajout de matériel unitaire en augmentant aussi la quantité totale
	 * 
	 * Caractéristiques supplémentaires :
	 * * La quantité totale est toujours égale à la nouvelle quantité identifiée unitairement
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @depends testImportUpdateMatosAddSomeUnits
	 * @covers ::importInventaire
	 */
	public function testImportUpdateMatosAddAllUnits() {
		// Préparation des données
		$file = 'SQL_backup_importUpdateMatosAddAllUnits.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel remplissant les conditions de test
		$unchangingDataSet = new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddAllUnits::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Un materiel non identifie'),
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddAllUnits::2',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Plusieurs materiels non identifies'),
					array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddAllUnits::3',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Augmentation de la quantite totale')),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddAllUnits::4',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 8, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddAllUnits::5',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 9, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddAllUnits::6',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'))));
		$this->insertData($unchangingDataSet);
		$this->insertData(new ArrayDataSet(array(
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 5, 'quantite' => 2, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 6, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null)))));
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données et notamment que les lignes dans robert_matos_generique ont été supprimées
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(),
				$unchangingDataSet, new ArrayDataSet(array(
					'robert_matos_unit' => array(
						array('id_matosunit' => 10, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddAllUnits::7',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
						array('id_matosunit' => 11, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddAllUnits::8',
							'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
						array('id_matosunit' => 12, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddAllUnits::9',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
						array('id_matosunit' => 13, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddAllUnits::10',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
						array('id_matosunit' => 14, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddAllUnits::11',
							'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des lignes d'ajout de matériel unitaire à du matériel existant sans que tout ne soit identifié unitairement.
	 * 
	 * Cas de test :
	 * 1. Ajout d'un seul matériel unitaire
	 * 2. Ajout de plusieurs matériels unitaires
	 * 3. Ajout de matériel unitaire sans changer la quantité totale
	 * 4. Ajout de matériel unitaire en diminuant la quantité totale
	 * 5. Ajout de matériel unitaire en augmentant la quantité totale, où la nouvelle quantité identifiée unitairement est inférieure à l'ancienne quantité totale
	 * 6. Ajout de matériel unitaire en augmentant la quantité totale, où la nouvelle quantité identifiée unitairement est supérieure à l'ancienne quantité totale
	 * 7. Ajout de matériel unitaire à un matériel qui n'était pas identifié
	 * 8. Ajout de matériel unitaire à un matériel qui était partiellement identifié
	 * 9. Ajout de matériel unitaire à un matériel qui était entièrement identifié (et augmentation de la quantité totale)
	 * 
	 * Caractéristiques supplémentaires :
	 * * La nouvelle quantité totale est toujours supérieure à la nouvelle quantité identifiée unitairement
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @covers ::importInventaire
	 */
	public function testImportUpdateMatosAddSomeUnits() {
		// Préparation des données
		$file = 'SQL_backup_importUpdateMatosAddSomeUnits.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel remplissant les conditions de test
		$unchangingDataSet = new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Ajout de plusieurs materiels identifies'),
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::2',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Diminution de la quantite'),
					array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::3',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Augmentation de la quantite'),
					array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::4',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Nouvelle quantite identifiee superieure a l'ancienne quantite totale"),
					array('id' => 8, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::5',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Materiel entierement identifie')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 6, 'quantite' => 2, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 7, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT')),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::6',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 8, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::7',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
					array('id_matosunit' => 9, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::8',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
					array('id_matosunit' => 10, 'id_matosdetail' => 8, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::9',
						'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'))));
		$this->insertData($unchangingDataSet);
		$this->insertData(new ArrayDataSet(array(
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 3, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 5, 'quantite' => 3, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT')))));
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données et notamment que les lignes dans robert_matos_generique ont été supprimées
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(),
				$unchangingDataSet, new ArrayDataSet(array(
					'robert_matos_generique' => array(
						array('id_matosdetail' => 4, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 5, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 8, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null)),
					'robert_matos_unit' => array(
						array('id_matosunit' => 11, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::10',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
						array('id_matosunit' => 12, 'id_matosdetail' => 4, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::11',
							'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
						array('id_matosunit' => 13, 'id_matosdetail' => 5, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::12',
							'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
						array('id_matosunit' => 14, 'id_matosdetail' => 6, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::13',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
						array('id_matosunit' => 15, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::14',
							'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Remarque'),
						array('id_matosunit' => 16, 'id_matosdetail' => 7, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::15',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'),
						array('id_matosunit' => 17, 'id_matosdetail' => 8, 'ref' => 'SQL_Backup_Test::testImportUpdateMatosAddSomeUnits::16',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Remarque'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire contenant des caractères spéciaux.
	 * 
	 * Cas de test :
	 * 1. Ligne contenant le bon nombre de caractères séparateurs (points-virgules) mais qui est invalide car certains de ces séparateurs sont échappés (entre double quotes)
	 * 2. Champs contenant des caractères accentués ASCII
	 * 3. Champs contenant des caractères Unicode non ASCII
	 * 4. Champs échapés entre double quotes contenant le caractère séparateur point-virgule
	 * 5. Champs échapés entre double quotes contenant aussi des doubles quotes (par paires)
	 * 6. Sous-catégorie existante dont le nom contient des caractères accentués
	 * 7. Sous-catégorie inexistante dont le nom contient des caractères accentués
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de lignes d'ajout de nouveau matériel générique et unitaire, et de modification de matériel existant
	 * * Alternance de matériel interne et externe
	 * 
	 * @test
	 * @depends testImportInvalidFormat
	 * @depends testImportNewMatosNoUnit
	 * @depends testImportNewMatosSomeUnits
	 * @depends testImportNewSousCategorie
	 * @depends testImportUpdateMatosAddSomeUnits
	 * @depends testImportUpdateMatosNotAllUnits
	 * @covers ::importInventaire
	 */
	public function testImportSpecialCharacters() {
		// Préparation des données
		$file = 'SQL_backup_importSpecialCharacters.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel existant
		$this->insertData(new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportSpecialCharacters::2',
						'categorie' => 'son', 'sousCateg' => 7, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Remarque'),
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportSpecialCharacters::4',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Remarque'),
					array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportSpecialCharacters::6',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Remarque')),
				'robert_matos_generique' => array(
						array('id_matosdetail' => 4, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 5, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 6, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null)))));
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(), new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Désignation', 'ref' => 'SQL_Backup_Test::testImportSpecialCharactersà2',
						'categorie' => 'son', 'sousCateg' => 7, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Champs accentués ASCII'),
					array('id' => 5, 'label' => 'Design;ation', 'ref' => 'SQL_Backup_Test::testImportSpecialCharacters;4',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Champs avec ;'),
					array('id' => 6, 'label' => 'De;sig"na;tion', 'ref' => 'SQL_Backup_Test::testImportSpecialCharacters";"6',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Champs avec ";" et """'),
					array('id' => 7, 'label' => 'обозначение', 'ref' => 'SQL_Backup_Test::testImportSpecialCharactersϾ3',
						'categorie' => 'son', 'sousCateg' => 7, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Champs α Unicode non ASCII'),
					array('id' => 8, 'label' => 'Desig"nation', 'ref' => 'SQL_Backup_Test::testImportSpecialCharacters"5',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Champs avec "'),
					array('id' => 9, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportSpecialCharacters::7',
						'categorie' => 'son', 'sousCateg' => 23, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Nouvelle sous-catégorie avec caractères spéciaux')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 5, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 6, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 7, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 8, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 9, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT')),
				'robert_matos_unit' => array(
					array('id_matosunit' => 7, 'id_matosdetail' => 9, 'ref' => 'SQL_Backup_Test::testImportSpecialCharacters";àα"8',
						'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Ajout avec ";", """, caractères accentués et Unicode α')),
				'robert_matos_sous_cat' => array(
					array('id' => 23, 'label' => '"Shure β" et micros à gogo !', 'ordre' => 23))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire dont les champs optionnels ne sont pas renseignés.
	 * 
	 * Cas de test :
	 * 1. Code-barres (2e champ) vide
	 * 2. Référence (3e champ) vide
	 * 3. Sous-catégorie (6e champ) vide
	 * 4. Quantité en panne (8e champ) vide
	 * 5. Tarif de location (9e champ) vide
	 * 6. Valeur de remplacement (10e champ) vide
	 * 7. Remarque (11e champ) vide
	 * 8. Date d'achat et prestataire externe (12e et 13e champs) vides
	 * 9. Lignes d'ajout de nouveau matériel
	 * 10. Lignes de modification de matériel existant
	 * 11. Lignes d'ajout de matériel unitaire
	 * 12. Lignes avec aucun des champs optionnels renseigné
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de matériel interne et externe
	 *
	 * @test
	 * @depends testImportNewMatosNoUnit
	 * @depends testImportNewMatosSomeUnits
	 * @depends testImportUpdateMatosNotAllUnits
	 * @covers ::importInventaire
	 */
	public function testImportDefaultValues() {
		// Préparation des données
		$file = 'SQL_backup_importDefaultValues.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel existant
		$unchangingDataSet = new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::4',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Quantite en panne vide'),
					array('id' => 11, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::8',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Date et prestataire vides')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 5, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 6, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 8, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 9, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 10, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null))));
		$this->insertData($unchangingDataSet);
		$this->insertData(new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::1_OLD',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Code-barres vide'), //TODO revoir codes-barres
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::2_OLD',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Reference vide'),
					array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::3',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Sous-categorie vide'),
					array('id' => 8, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::5',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Tarif de location vide'),
					array('id' => 9, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::6',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Valeur de remplacement vide'),
					array('id' => 10, 'label' => 'Remarque vide', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::7',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Remarque vide'),
					array('id' => 12, 'label' => 'Tous champs optionnels vides', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::9_OLD',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Tous champs optionnels vides')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 7, 'quantite' => 1, 'panne' => 1, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 11, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 12, 'quantite' => 1, 'panne' => 1, 'dateAchat' => null, 'ownerExt' => 'EXT')))));
		
		// Test
		$this->callTestedPage();
		
		// Vérification du message retourné
		$this->assertSame("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(),
				$unchangingDataSet, new ArrayDataSet(array(
					'robert_matos_detail' => array(
						array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::1',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Code-barres vide'),
						array('id' => 5, 'label' => 'Designation', 'ref' => '000002',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Reference vide'),
						array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::3',
							'categorie' => 'son', 'sousCateg' => 0, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Sous-categorie vide'),
						array('id' => 8, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::5',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 0.0, 'valRemp' => 2.0, 'remarque' => 'Tarif de location vide'),
						array('id' => 9, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::6',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 0.0, 'remarque' => 'Valeur de remplacement vide'),
						array('id' => 10, 'label' => 'Remarque vide', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::7',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => null),
						array('id' => 12, 'label' => 'Tous champs optionnels vides', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::9',
							'categorie' => 'son', 'sousCateg' => 0, 'tarifLoc' => 0.0, 'valRemp' => 0.0, 'remarque' => null),
						array('id' => 13, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::10',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Code-barres vide'),
						array('id' => 14, 'label' => 'Designation', 'ref' => '000011',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Reference vide'),
						array('id' => 15, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::12',
							'categorie' => 'son', 'sousCateg' => 0, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Sous-categorie vide'),
						array('id' => 16, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::13',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Quantite en panne vide'),
						array('id' => 17, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::14',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 0.0, 'valRemp' => 2.0, 'remarque' => 'Tarif de location vide'),
						array('id' => 18, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::15',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 0.0, 'remarque' => 'Valeur de remplacement vide'),
						array('id' => 19, 'label' => 'Remarque vide', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::16',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => null),
						array('id' => 20, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::17',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Date et prestataire vides'),
						array('id' => 21, 'label' => 'Tous champs optionnels vides', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::18',
							'categorie' => 'son', 'sousCateg' => 0, 'tarifLoc' => 0.0, 'valRemp' => 0.0, 'remarque' => null),
						array('id' => 22, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportDefaultValues::19',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Ligne valide avant tests d'ajouts unitaires")),
					'robert_matos_generique' => array(
						array('id_matosdetail' => 7, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 11, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '0000-00-00', 'ownerExt' => null),
						array('id_matosdetail' => 12, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '0000-00-00', 'ownerExt' => null),
						array('id_matosdetail' => 13, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 14, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 15, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 16, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 17, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 18, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 19, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 20, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '0000-00-00', 'ownerExt' => null),
						array('id_matosdetail' => 21, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '0000-00-00', 'ownerExt' => null),
						array('id_matosdetail' => 22, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null)),
					'robert_matos_unit' => array(
						array('id_matosunit' => 7, 'id_matosdetail' => 22, 'ref' => 'SQL_Backup_Test::testImportDefaultValues::20',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Code-barres vide'),
						array('id_matosunit' => 8, 'id_matosdetail' => 22, 'ref' => '000021',
							'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Reference vide'),
						array('id_matosunit' => 9, 'id_matosdetail' => 22, 'ref' => 'SQL_Backup_Test::testImportDefaultValues::22',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Quantite en panne vide'),
						array('id_matosunit' => 10, 'id_matosdetail' => 22, 'ref' => 'SQL_Backup_Test::testImportDefaultValues::23',
							'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => null),
						array('id_matosunit' => 11, 'id_matosdetail' => 22, 'ref' => 'SQL_Backup_Test::testImportDefaultValues::24',
							'panne' => 0, 'dateAchat' => '0000-00-00', 'ownerExt' => null, 'remarque' => 'Date et prestataire vides'),
						array('id_matosunit' => 12, 'id_matosdetail' => 22, 'ref' => 'SQL_Backup_Test::testImportDefaultValues::25',
							'panne' => 0, 'dateAchat' => '0000-00-00', 'ownerExt' => null, 'remarque' => null))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
	
	/**
	 * Teste l'import d'un fichier d'inventaire dont les champs sont trop longs pour entrer en base de données.
	 * 
	 * Cas de test :
	 * 1. Longueur du code-barres (2e champ) supérieure de 1 caractère à la taille maximale
	 * 2. Longueur de la référence (3e champ) supérieure de 1 caractère à la taille maximale
	 * 3. Longueur de la désignation (4e champ) supérieure de 1 caractère à la taille maximale
	 * 4. Quantité totale (7e champ) égale au plus grand entier possible
	 * 5. Quantité totale (7e champ) supérieure de 1 au plus grand entier possible
	 * 6. Quantité en panne (8e champ) égale au plus grand entier possible
	 * 7. Quantité en panne (8e champ) supérieure de 1 au plus grand entier possible
	 * 8. Tarif de location (9e champ) supérieur au plus grand nombre à virgule flottante possible
	 * 9. Valeur de remplacement (10e champ) supérieure au plus grand nombre à virgule flottante possible
	 * 10. Remarque (11e champ) supérieure de 1 caractère à la taille maximale
	 * 11. Date d'achat (12e champ) avec tous les champs de la date à leur valeur maximale
	 * 12. Longueur du prestataire externe (13e champ) supérieure de 1 caractère à la taille maximale
	 * 13. Lignes de modification de matériel existant
	 * 14. Lignes d'ajout de nouveau matériel
	 * 15. Lignes d'ajout de matériel unitaire
	 * 
	 * Caractéristiques supplémentaires :
	 * * Alternance de matériel interne et externe
	 *
	 * @test
	 * @depends testImportNewMatosNoUnit
	 * @depends testImportNewMatosSomeUnits
	 * @depends testImportUpdateMatosNotAllUnits
	 * @covers ::importInventaire
	 */
	public function testImportOverflow() {
		// Préparation des données
		$file = 'SQL_backup_importOverflow.csv';
		$_POST['import'] = 'matos';
		$_POST['fileBackup'] = $file;
		// Ajout de matériel existant
		$unchangingDataSet = new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 4, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::1',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Code-barres trop long'), //TODO revoir codes-barres
					array('id' => 7, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::4',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Quantite max'),
					array('id' => 8, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::5',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Quantite trop grande'),
					array('id' => 9, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::6',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Quantite en panne max'),
					array('id' => 10, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::7',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Quantite en panne trop grande'),
					array('id' => 14, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::11',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Date max'),
					array('id' => 15, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::12',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Prestataire trop long')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 4, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 5, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 6, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 8, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 10, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 11, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 12, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 13, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'))));
		$this->insertData($unchangingDataSet);
		$this->insertData(new ArrayDataSet(array(
				'robert_matos_detail' => array(
					array('id' => 5, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::2',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Reference trop longue'),
					array('id' => 6, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::3',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Designation trop longue'),
					array('id' => 11, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::8',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Tarif de location trop grand'),
					array('id' => 12, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::9',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Valeur de remplacement trop grande'),
					array('id' => 13, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::10',
						'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Remarque trop longue')),
				'robert_matos_generique' => array(
					array('id_matosdetail' => 7, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 9, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
					array('id_matosdetail' => 14, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
					array('id_matosdetail' => 15, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT')))));
		
		// Test
		$this->callTestedPage();
		
		// Vérification des messages d'erreur
		foreach(array(5, 17) as $row) {
			$this->assertContains("WARNING ligne $row ignorée : quantité invalide.<br />", $this->getOutput());
		}
		foreach(array(7, 19) as $row) {
			$this->assertContains("WARNING ligne $row ignorée : quantité en panne invalide.<br />", $this->getOutput());
		}
		$this->assertStringEndsWith("</b>IMPORT de <b>$file</b> OK !", $this->getOutput());
		// Vérification du contenu de la base de données
		$expectedDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->getDataSet(),
				$unchangingDataSet, new ArrayDataSet(array(
					'robert_matos_detail' => array(
						array('id' => 5, 'label' => 'Designation', 'ref' => str_pad('129a', 128, 'X'),
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Reference trop longue'),
						array('id' => 6, 'label' => str_pad('257', 256, 'X'), 'ref' => 'SQL_Backup_Test::testImportOverflow::3',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Designation trop longue'),
						array('id' => 11, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::8',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 3.40282E+38, 'valRemp' => 2.0, 'remarque' => 'Tarif de location trop grand'),
						array('id' => 12, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::9',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 3.40282E+38, 'remarque' => 'Valeur de remplacement trop grande'),
						array('id' => 13, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::10',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => str_pad('Remarque trop longue (65536) > ', 65535, 'X')),
						array('id' => 16, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::13',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Code-barres trop long'), //TODO revoir codes-barres
						array('id' => 17, 'label' => 'Designation', 'ref' => str_pad('129b', 128, 'X'),
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Reference trop longue'),
						array('id' => 18, 'label' => str_pad('257', 256, 'X'), 'ref' => 'SQL_Backup_Test::testImportOverflow::15',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Designation trop longue'),
						array('id' => 19, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::16',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Quantite max'),
						array('id' => 20, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::18',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Quantite en panne max'),
						array('id' => 21, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::20',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 3.40282E+38, 'valRemp' => 2.0, 'remarque' => 'Tarif de location trop grand'),
						array('id' => 22, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::21',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 3.40282E+38, 'remarque' => 'Valeur de remplacement trop grande'),
						array('id' => 23, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::22',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => str_pad('Remarque trop longue (65536) > ', 65535, 'X')),
						array('id' => 24, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::23',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Date max'),
						array('id' => 25, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::24',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => 'Prestataire trop long'),
						array('id' => 26, 'label' => 'Designation', 'ref' => 'SQL_Backup_Test::testImportOverflow::25',
							'categorie' => 'son', 'sousCateg' => 5, 'tarifLoc' => 1.0, 'valRemp' => 2.0, 'remarque' => "Ligne valide avant tests d'ajouts unitaires")),
					'robert_matos_generique' => array(
						array('id_matosdetail' => 7, 'quantite' => 2147483647, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 9, 'quantite' => 2147483647, 'panne' => 2147483647, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 14, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '9999-12-31', 'ownerExt' => null),
						array('id_matosdetail' => 15, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => str_pad('257', 256, 'X')),
						array('id_matosdetail' => 16, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 17, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 18, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 19, 'quantite' => 2147483647, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 20, 'quantite' => 2147483647, 'panne' => 2147483647, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 21, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 22, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT'),
						array('id_matosdetail' => 23, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null),
						array('id_matosdetail' => 24, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '9999-12-31', 'ownerExt' => null),
						array('id_matosdetail' => 25, 'quantite' => 1, 'panne' => 0, 'dateAchat' => null, 'ownerExt' => str_pad('257', 256, 'X')),
						array('id_matosdetail' => 26, 'quantite' => 1, 'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null)),
					'robert_matos_unit' => array(
						array('id_matosunit' => 7, 'id_matosdetail' => 26, 'ref' => 'SQL_Backup_Test::testImportOverflow::26',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => 'Code-barres trop long'), //TODO revoir codes-barres
						array('id_matosunit' => 8, 'id_matosdetail' => 26, 'ref' => str_pad('129c', 128, 'X'),
							'panne' => 0, 'dateAchat' => '2016-05-31', 'ownerExt' => null, 'remarque' => 'Reference trop longue'),
						array('id_matosunit' => 9, 'id_matosdetail' => 26, 'ref' => 'SQL_Backup_Test::testImportOverflow::28',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => 'EXT', 'remarque' => str_pad('Remarque trop longue (65536) > ', 65535, 'X')),
						array('id_matosunit' => 10, 'id_matosdetail' => 26, 'ref' => 'SQL_Backup_Test::testImportOverflow::29',
							'panne' => 0, 'dateAchat' => '9999-12-31', 'ownerExt' => null, 'remarque' => 'Date max'),
						array('id_matosunit' => 11, 'id_matosdetail' => 26, 'ref' => 'SQL_Backup_Test::testImportOverflow::30',
							'panne' => 0, 'dateAchat' => null, 'ownerExt' => str_pad('257', 256, 'X'), 'remarque' => 'Prestataire trop long'))))));
		foreach (self::$tables as $tableName) {
			$this->assertTableContents($expectedDataSet->getTable($tableName), $tableName);
		}
	}
}
?>