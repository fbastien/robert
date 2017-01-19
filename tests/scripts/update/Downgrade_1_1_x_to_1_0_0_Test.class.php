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

require_once dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'CustomDatabaseTestCase.class.php';

/**
 * Test du script de retour en arrière de la base de données des versions 1.1.x vers 1.0.0 (downgrade_1.1.x_to_1.0.0.sql).
 * 
 * Cas de test (des données en version 1.1.x) :
 * 1. Matériel non identifié unitairement
 * 2. Matériel partiellement identifié unitairement
 * 3. Matériel entièrement identifié unitairement
 * 4. Matériel interne avec unités identifiées et sans aucune date d'achat renseignée
 * 5. Matériel interne avec date d'achat globale renseignée, mais renseignée sur aucune de ses unités
 * 6. Matériel interne avec date d'achat globale non renseignée, mais renseignée sur au moins une unité (et aussi non renseignée sur au moins une autre unité)
 * 7. Matériel interne avec date d'achat d'une unité antérieure à la date d'achat globale
 * 8. Matériel externe avec prestataire global renseigné à vide
 * 9. Matériel externe avec prestataire renseigné sur aucune de ses unités
 * 10. Matériel externe avec prestataire renseigné sur au moins 2 unités (et aussi non renseigné sur au moins un autre unité)
 * 11. Matériel sans remarque
 * 12. Matériel avec remarque globale mais aucune remarque sur ses unités
 * 13. Matériel sans remarque globale mais avec remarques sur ses unités (et au moins une unité sans remarque)
 * 14. Matériel avec remarque globale et remarques sur ses unités (au moins 2)
 * 
 * @group db
 */
class Downgrade_1_1_x_to_1_0_0_Test extends CustomDatabaseTestCase
{
	/** @var PHPUnit_Extensions_Database_DataSet_IDataSet DataSet correspondant à la structure attendue de la BDD après mise à jour. */
	private static $expectedSchema;
	/** @var PHPUnit_Extensions_Database_DataSet_IDataSet DataSet correspondant au contenu attendu de la BDD après mise à jour. */
	private static $expectedData;
	
	/** @see PHPUnit_Framework_TestCase::setUpBeforeClass() */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		// Installe la base de données dans la version postérieure
		$instance = new self();
		$instance->installDatabase(Version::V1_1_0());
		// Charge les datasets
		self::$expectedSchema = new PHPUnit_Extensions_Database_DataSet_XmlDataSet(
				dirname(__DIR__).DIRECTORY_SEPARATOR.'DB_schema'.DIRECTORY_SEPARATOR.'DB_schema_1.0.0_dataset.xml');
		self::$expectedData = new PHPUnit_Extensions_Database_DataSet_XmlDataSet(
				__DIR__.DIRECTORY_SEPARATOR.'downgrade_1.1.x_to_1.0.0_expected_dataset.xml');
	}
	
	/** @test */
	public function testScript() {
		// Insertion des données de test
		$this->insertData($this->createXmlDataSet(
				__DIR__.DIRECTORY_SEPARATOR.'downgrade_1.1.x_to_1.0.0_fixture_dataset.xml'));
		
		$this->executeScript(
				dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'update'.DIRECTORY_SEPARATOR.'downgrade_1.1.x_to_1.0.0.sql');
	}
	
	/**
	 * @test
	 * @depends testScript
	 */
	public function testTables() {
		$this->assertTableList(self::$expectedSchema->getTable('TABLES'));
	}
	
	/**
	 * @test
	 * @depends testTables
	 */
	public function testTableColumns() {
		$this->assertTableColumns(self::$expectedSchema->getTable('COLUMNS'));
		$this->assertViewColumns(self::$expectedSchema->getTable('VIEW_COLUMNS'));
	}
	
	/** Data provider indiquant les tables dont il faut tester le contenu. */
	public function provideTables() {
		return array(
				array('robert_matos_detail'),
				array('robert_users')
			);
	}
	
	/**
	 * @test
	 * @dataProvider provideTables
	 * @depends testTableColumns
	 */
	public function testTableContents($tableName) {
		$this->assertTableContents(self::$expectedData->getTable($tableName), $tableName);
	}
}
?>