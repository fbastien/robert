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
	
	/** @return PHPUnit_Extensions_Database_DataSet_IDataSet */
	public function getDataSet() {
		return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
	}
	
	public static function setUpBeforeClass() {
		// Vide la base de données
		$instance = new Install_DB_Test();
		$instance->truncateDatabase();
	}
	
	/**
	 * Teste la bonne exécution du script d'installation de la base de données (install_DB.sql).
	 * 
	 * La structure et le contenu des tables ainsi cré
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
				'  SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_COLLATION'
				.' FROM information_schema.TABLES'
				.' WHERE TABLE_SCHEMA = \''.$GLOBALS['PHPUNIT_DB_DBNAME'].'\'');
		$expectedTable = $this->createXmlDataSet(__DIR__.'/../DB_schema/DB-schema-1.0.0-dataset.xml')->getTable('TABLES');
		
		$this->assertTablesEqual($expectedTable, $actualTable);
	}
	
	/**
	 * @test
	 * @depends testTables
	 */
	public function testTableColumns() {
		$actualTable = $this->getConnection()->createQueryTable('COLUMNS',
				'  SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_TYPE, COLUMN_KEY, COLUMN_DEFAULT, IS_NULLABLE, EXTRA, CHARACTER_SET_NAME, COLLATION_NAME'
				.' FROM information_schema.COLUMNS'
				.' WHERE TABLE_SCHEMA = \''.$GLOBALS['PHPUNIT_DB_DBNAME'].'\'');
		$expectedTable = $this->createXmlDataSet(__DIR__.'/../DB_schema/DB-schema-1.0.0-dataset.xml')->getTable('COLUMNS');
		
		$this->assertTablesEqual($expectedTable, $actualTable);
	}
	
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
		$actualTable = $this->getConnection()->createQueryTable($tableName, 'SELECT * FROM '.$tableName);
		$expectedTable = $this->createXmlDataSet(__DIR__.'/../DB_schema/DB-schema-1.0.0-dataset.xml')->getTable($tableName);
		
		$this->assertTablesEqual($expectedTable, $actualTable);
	}
}
?>