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

require_once __DIR__.'/../../Database_Testcase.class.php';

class Install_DB_Test extends Database_Testcase {
	
	/** Dataset correspondant à la structure attendue de la BDD. */
	private static $dataset;
	
	/** @return PHPUnit_Extensions_Database_DataSet_IDataSet */
	public function getDataSet() {
		return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
	}
	
	public static function setUpBeforeClass() {
		// Vide la base de données
		$instance = new Install_DB_Test();
		$instance->truncateDatabase();
		// Charge le dataset de la structure de la BDD
		self::$dataset = $instance->createXmlDataSet(__DIR__.'/../DB_schema/DB-schema-1.0.0-dataset.xml');
	}
	
	/**
	 * Teste la bonne exécution du script d'installation de la base de données (install_DB.sql).
	 * 
	 * La structure et le contenu des tables ainsi créées est vérifié dans les autres tests.
	 * 
	 * @test
	 */
	public function testScript() {
		$this->executeScript(__DIR__.'/../../../scripts/install/install_DB.sql');
	}
	
	/**
	 * @test
	 * @depends testScript
	 */
	public function testTables() {
		$actualTable = $this->getConnection()->createQueryTable('TABLES',
				"  SELECT `TABLE_NAME`, `TABLE_TYPE`, `ENGINE`, `TABLE_COLLATION`"
				." FROM `information_schema`.`TABLES`"
				." WHERE `TABLE_SCHEMA` = '{$GLOBALS['PHPUNIT_DB_DBNAME']}'");
		$expectedTable = self::$dataset->getTable('TABLES');
		
		$this->assertTablesEqual($expectedTable, $actualTable);
	}
	
	/**
	 * @test
	 * @depends testTables
	 */
	public function testTableColumns() {
		$query = " SELECT {columns}"
				." FROM `information_schema`.`COLUMNS`"
				." WHERE `TABLE_SCHEMA` = '{$GLOBALS['PHPUNIT_DB_DBNAME']}'"
				."   AND (SELECT `TABLE_TYPE`"
				."       FROM `information_schema`.`TABLES`"
				."       WHERE `TABLE_SCHEMA` = `COLUMNS`.`TABLE_SCHEMA`"
				."         AND `TABLE_NAME` = `COLUMNS`.`TABLE_NAME`)"
				."     LIKE '%{type}%'";
		
		// Tables
		$actualTable = $this->getConnection()->createQueryTable('COLUMNS',
				str_replace(array('{columns}', '{type}'),
						array('`TABLE_NAME`, `COLUMN_NAME`, `ORDINAL_POSITION`, `COLUMN_TYPE`, `COLUMN_KEY`, `COLUMN_DEFAULT`, `IS_NULLABLE`, `EXTRA`, `CHARACTER_SET_NAME`, `COLLATION_NAME`',
							'TABLE'),
						$query));
		$expectedTable = self::$dataset->getTable('COLUMNS');
		$this->assertTablesEqual($expectedTable, $actualTable);
		
		// Vues
		$actualTable = $this->getConnection()->createQueryTable('VIEW_COLUMNS',
				str_replace(array('{columns}', '{type}'),
						array('`TABLE_NAME`, `COLUMN_NAME`, `ORDINAL_POSITION`, `DATA_TYPE`',
							'VIEW'),
						$query));
		$expectedTable = self::$dataset->getTable('VIEW_COLUMNS');
		$this->assertTablesEqual($expectedTable, $actualTable);
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
		$actualTable = $this->getConnection()->createQueryTable($tableName, "SELECT * FROM $tableName");
		$expectedTable = self::$dataset->getTable($tableName);
		
		$this->assertTablesEqual($expectedTable, $actualTable);
	}
}
?>