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
 * Test du script d'installation de la base de données (install_DB.sql).
 * 
 * @group db
 */
class Install_DB_Test extends Database_Testcase {
	
	/** @var PHPUnit_Extensions_Database_DataSet_IDataSet DataSet correspondant à la structure attendue de la BDD. */
	private static $dataset;
	
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
		// Vide la base de données
		$instance = new self();
		$instance->truncateDatabase();
		// Charge le dataset de la structure de la BDD
		self::$dataset = $instance->createXmlDataSet(dirname(__DIR__).'/DB_schema/DB_schema_'.Version::last()->value().'_dataset.xml');
	}
	
	/**
	 * Teste l'exécution sans erreur du script d'installation.
	 * 
	 * La structure et le contenu des tables ainsi créées est vérifié dans les autres tests.
	 * 
	 * @test
	 */
	public function testScript() {
		$this->executeScript(dirname(dirname(dirname(__DIR__))).'/scripts/install/install_DB.sql');
	}
	
	/**
	 * @test
	 * @depends testScript
	 */
	public function testTables() {
		$this->assertTableList(self::$dataset->getTable('TABLES'));
	}
	
	/**
	 * @test
	 * @depends testTables
	 */
	public function testTableColumns() {
		$this->assertTableColumns(self::$dataset->getTable('COLUMNS'));
		$this->assertViewColumns(self::$dataset->getTable('VIEW_COLUMNS'));
	}
	
	/** Data provider indiquant les tables dont il faut tester le contenu. */
	public function provideTablesWithContents() {
		return array(
				array('robert_matos_sous_cat'),
				array('robert_users')
			);
	}
	
	/**
	 * @test
	 * @dataProvider provideTablesWithContents
	 * @depends testTableColumns
	 */
	public function testTableContents($tableName) {
		$this->assertTableContents(self::$dataset->getTable($tableName), $tableName);
	}
}
?>