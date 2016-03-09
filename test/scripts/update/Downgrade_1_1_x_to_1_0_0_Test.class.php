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

require_once dirname(dirname(__DIR__)).'/Database_Testcase.class.php';

/**
 * Test du script de retour en arrière de la base de données des versions 1.1.x vers 1.0.0 (downgrade_1.1.x_to_1.0.0.sql).
 * 
 * @group db
 */
class Downgrade_1_1_x_to_1_0_0_Test extends Database_Testcase {
	
	/** @var PHPUnit_Extensions_Database_DataSet_IDataSet DataSet correspondant à la structure attendue de la BDD après mise à jour. */
	private static $expectedSchema;
	
	/** @var PHPUnit_Extensions_Database_DataSet_IDataSet DataSet correspondant au contenu attendu de la BDD après mise à jour. */
	private static $expectedData;
	
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	public function getDataSet() {
		return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
	}
	
	/**
	 * @beforeClass
	 * @see PHPUnit_Framework_TestCase::setUpBeforeClass()
	 */
	public static function setUpBeforeClass() {
		// Installe la base de données dans la version postérieure
		$instance = new self();
		$instance->installDatabase(Version::V1_1_0());
		// Charge les datasets
		self::$expectedSchema = $instance->createXmlDataSet(dirname(__DIR__).'/DB_schema/DB_schema_1.0.0_dataset.xml');
		self::$expectedData = $instance->createXmlDataSet(__DIR__.'/downgrade_1.1.x_to_1.0.0_expected_dataset.xml');
	}
	
	/** @test */
	public function testScript() {
		// Insertion des données de test
		$this->insertData($this->createXmlDataSet(__DIR__.'/downgrade_1.1.x_to_1.0.0_fixture_dataset.xml'));
		
		$this->executeScript(dirname(dirname(dirname(__DIR__))).'/scripts/update/downgrade_1.1.x_to_1.0.0.sql');
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
				array('robert_matos_detail')
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