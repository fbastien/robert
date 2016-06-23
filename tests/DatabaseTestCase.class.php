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

require_once __DIR__.DIRECTORY_SEPARATOR.'Version.class.php';

abstract class DatabaseTestCase extends PHPUnit_Extensions_Database_TestCase
{
	/** @var PDO only instantiate once for test clean-up/fixture load */
	private static $pdo;
	/** @var PHPUnit_Extensions_Database_DataSet_IDataSet Dataset des données communes à tous les cas de test. */
	private static $defaultDataset;
	
	/** @var PHPUnit_Extensions_Database_DB_IDatabaseConnection only instantiate once per test */
	private $conn;
	
	/** Initialise les propriétés de classe (appelé après la définition de la classe). */
	static function staticInit() {
		self::$pdo = new PDO(DSN, USER, PASS);
		self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		self::$defaultDataset = new PHPUnit_Extensions_Database_DataSet_XmlDataSet(__DIR__.DIRECTORY_SEPARATOR.'common_fixture_dataset.xml');
	}
	
	/** {@inheritDoc} */
	public function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
		$this->conn = $this->createDefaultDBConnection(self::$pdo, DBNAME);
	}
	
	/** {@inheritDoc} */
	public function __sleep() {
		// Ne pas sérialiser le PDO
		return array();
	}
	
	/**
	 * {@inheritDoc}
	 * 
	 * Installe la base de données dans sa dernière version.
	 * 
	 * @see PHPUnit_Framework_TestCase::setUpBeforeClass()
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		
		self::doInstallDatabase(self::$pdo);
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
	 * @see PHPUnit_Extensions_Database_TestCase::getConnection()
	 */
	public final function getConnection()
	{
		return $this->conn;
	}
	
	/**
	 * {@inheritDoc}
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	public function getDataSet() {
		return self::$defaultDataset;
	}
	
	/**
	 * Récupère le dataset par défaut pour les données de test, indépendemment du fait que la méthode getDataSet() soit redéfinie.
	 * 
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	protected final function getDefaultDataSet() {
		return self::$defaultDataset;
	}
	
	private static function doTruncateDatabase(PDO $pdo) {
		$tableList = $pdo->query("SELECT `TABLE_NAME`, `TABLE_TYPE` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = '".DBNAME."'");
		foreach($tableList as $table) {
			switch($table['TABLE_TYPE']) {
				case 'BASE TABLE':
					$query = "DROP TABLE `{$table['TABLE_NAME']}`";
					break;
				case 'VIEW':
					$query = "DROP VIEW `{$table['TABLE_NAME']}`";
					break;
				default:
					throw new RuntimeException("Type de table inconnu : {$table['TABLE_TYPE']}");
			}
			$pdo->exec($query);
		}
	}
	
	/** Vide la base de données de toutes ses tables. */
	protected function truncateDatabase() {
		self::doTruncateDatabase(self::$pdo);
	}
	
	private static function doExecuteScript(PDO $pdo, $file) {
		if(!extension_loaded('mysqli')) {
			throw new RuntimeException('Extension MySQLi manquante');
		}
		
		$script = file_get_contents($file);
		if(mb_detect_encoding($script, 'UTF-8', true) != 'UTF-8') {
			throw new PHPUnit_Framework_AssertionFailedError("L'encodage du script \"$file\" n'est pas UTF-8");
		}
		
		$mysqli = new mysqli(HOST, USER, PASS, DBNAME);
		if ($mysqli->connect_errno) {
			throw new RuntimeException("Erreur de connexion à la base de données ($mysqli->connect_errno) : $mysqli->connect_error");
		}
		// TODO PHP5.5+ : close() dans bloc finally
		if($mysqli->character_set_name() != 'utf8') {
			if(!$mysqli->set_charset('utf8')) {
				$mysqli->close();
				throw new RuntimeException("Erreur de configuration du jeu de caractères de la connexion à la base de données ($mysqli->errno) : $mysqli->error");
			}
		}
		
		$isSuccess = $mysqli->multi_query($script);
		
		while(true) {
			if(!$isSuccess) {
				$errMessage = "Erreur lors de l'exécution du script \"$file\" ($mysqli->errno/$mysqli->sqlstate) : $mysqli->error";
				$mysqli->close();
				throw new PHPUnit_Framework_AssertionFailedError($errMessage);
			}
			if(!$mysqli->more_results()) {
				break;
			}
			$isSuccess = $mysqli->next_result();
		}
		$mysqli->close();
	}
	
	/**
	 * Exécute un fichier de script SQL.
	 * 
	 * Nécessite l'extension MySQLi (car PDO ne permet pas d'exécuter plusieurs requêtes à la fois).
	 * Le script doit être encodé en UTF-8.
	 * 
	 * @param string $file Chemin du fichier à exécuter.
	 * @param boolean $isTest
	 *     Indique si cette méthode est appelée dans le cadre d'un test unitaire (true, par défaut) ou autre (setUp ou tearDown par exemple).
	 *     En cas d'erreur dans le script, dans le premier cas cela fera échouer le test, alors que sinon c'est une exception qui sera levée.
	 * @throws RuntimeException En cas d'erreur de connexion ou pendant l'exécution du script, ou si le script n'est pas encodé en UTF-8.
	 */
	protected function executeScript($file, $isTest = true) {
		try {
			self::doExecuteScript(self::$pdo, $file);
		} catch(PHPUnit_Framework_AssertionFailedError $error) {
			if($isTest) {
				$this->fail($error->getMessage());
			} else {
				throw new RuntimeException($error->getMessage());
			}
		}
	}
	
	private static function doInstallDatabase(PDO $pdo, Version $version = null) {
		// Vide la base de données
		self::doTruncateDatabase($pdo);
		
		// Détermine le script à exécuter
		if($version == null) {
			$version = Version::last();
		}
		$script = dirname(__DIR__).DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR;
		if($version == Version::last()) {
			$script .= 'install_DB.sql';
		} else {
			$script .= 'old'.DIRECTORY_SEPARATOR."install_DB_{$version->value()}.sql";
		}
		
		// Installe la base de données (cf. Install_DB_Test)
		try {
			self::doExecuteScript($pdo, $script);
		} catch (PHPUnit_Framework_AssertionFailedError $error) {
			throw new RuntimeException($error->getMessage());
		}
	}
	
	/**
	 * Vide la base de données et la réinstalle dans une version donnée.
	 * 
	 * @param Version $version Version à installer (la dernière par défaut).
	 */
	protected function installDatabase(Version $version = null) {
		self::doInstallDatabase(self::$pdo, $version);
	}
	
	/**
	 * Insère en base les données correspondant à un DataSet.
	 * 
	 * @param PHPUnit_Extensions_Database_DataSet_IDataSet $dataset Données à insérer en base.
	 */
	protected function insertData(PHPUnit_Extensions_Database_DataSet_IDataSet $dataset) {
		PHPUnit_Extensions_Database_Operation_Factory::INSERT()->execute($this->getConnection(), $dataset);
	}
	
	/**
	 * Récupère le contenu d'une table dans la base de données.
	 * 
	 * @param string $tableName Nom de la table à interroger.
	 * @return PHPUnit_Extensions_Database_DataSet_QueryTable Dataset contenant les données de la table
	 */
	protected function queryData($tableName) {
		return $this->getConnection()->createQueryTable($tableName, "SELECT * FROM `$tableName`");
	}
	
	/**
	 * Vérifie la liste des tables et vues dans la base de données.
	 * 
	 * @param PHPUnit_Extensions_Database_DataSet_ITable $expected DataSet contenant la liste des tables et vues attendues.
	 */
	protected final function assertTableList(PHPUnit_Extensions_Database_DataSet_ITable $expected) {
		$actual = $this->getConnection()->createQueryTable('TABLES',
				"  SELECT `TABLE_NAME`, `TABLE_TYPE`, `ENGINE`, `TABLE_COLLATION`"
				." FROM `information_schema`.`TABLES`"
				." WHERE `TABLE_SCHEMA` = '".DBNAME."'");
		$this->assertTablesEqual($expected, $actual, 'La liste des tables et vues est incorrecte.');
	}
	
	/**
	 * Vérifie la structure des tables dans la base de données.
	 * 
	 * @param PHPUnit_Extensions_Database_DataSet_ITable $expected DataSet contenant la structure attendue des tables.
	 */
	protected final function assertTableColumns(PHPUnit_Extensions_Database_DataSet_ITable $expected) {
		$actual = $this->getConnection()->createQueryTable('COLUMNS',
				" SELECT `TABLE_NAME`, `COLUMN_NAME`, `ORDINAL_POSITION`, `COLUMN_TYPE`, `COLUMN_KEY`, `COLUMN_DEFAULT`, `IS_NULLABLE`, `EXTRA`, `CHARACTER_SET_NAME`, `COLLATION_NAME`"
				." FROM `information_schema`.`COLUMNS`"
				." WHERE `TABLE_SCHEMA` = '".DBNAME."'"
				."   AND (SELECT `TABLE_TYPE`"
				."       FROM `information_schema`.`TABLES`"
				."       WHERE `TABLE_SCHEMA` = `COLUMNS`.`TABLE_SCHEMA`"
				."         AND `TABLE_NAME` = `COLUMNS`.`TABLE_NAME`)"
				."     LIKE '%TABLE%'");
		$this->assertTablesEqual($expected, $actual, 'La structure des tables est incorrecte.');
	}
	
	/**
	 * Vérifie la structure des vues dans la base de données.
	 * 
	 * @param PHPUnit_Extensions_Database_DataSet_ITable $expected DataSet contenant la structure attendue des vues.
	 */
	protected final function assertViewColumns(PHPUnit_Extensions_Database_DataSet_ITable $expected) {
		$actual = $this->getConnection()->createQueryTable('VIEW_COLUMNS',
				" SELECT `TABLE_NAME`, `COLUMN_NAME`, `ORDINAL_POSITION`, `DATA_TYPE`"
				." FROM `information_schema`.`COLUMNS`"
				." WHERE `TABLE_SCHEMA` = '".DBNAME."'"
				."   AND (SELECT `TABLE_TYPE`"
				."       FROM `information_schema`.`TABLES`"
				."       WHERE `TABLE_SCHEMA` = `COLUMNS`.`TABLE_SCHEMA`"
				."         AND `TABLE_NAME` = `COLUMNS`.`TABLE_NAME`)"
				."     LIKE '%VIEW%'");
		$this->assertTablesEqual($expected, $actual, 'La structure des vues est incorrecte.');
	}
	
	/**
	 * Vérifie le contenu d'une table dans la base de données.
	 * 
	 * @param PHPUnit_Extensions_Database_DataSet_ITable $expected DataSet contenant les valeurs attendues dans la table.
	 * @param unknown $actualTableName Nom de la table à tester dans la base de données.
	 */
	protected final function assertTableContents(PHPUnit_Extensions_Database_DataSet_ITable $expected, $actualTableName) {
		$this->assertTablesEqual($expected, $this->queryData($actualTableName), "Le contenu de la table '$actualTableName' est incorrect.");
	}
	
	/**
	 * Vérifie que le code des vues (a priori après script de mise à jour) est identique à celui présent dans le script d'installation de la base de données.
	 * 
	 * Attention ! Cette méthode réinstalle la base de données. N'utiliser qu'en fin de cas de test.
	 * 
	 * @param Version $version Version du script d'installation avec laquelle comparer le code des vues.
	 */
	protected final function assertViewFormulaSameAsInstall(Version $version) {
		// Récupère la formule actuelle des vues (a priori après script de mise à jour).
		$viewsFormulaQuery =
			"  SELECT `TABLE_NAME`, `VIEW_DEFINITION`"
			." FROM `information_schema`.`VIEWS`"
			." WHERE `TABLE_SCHEMA` = '".DBNAME."'";
		$viewsFromUpdate = $this->getConnection()->createQueryTable('VIEWS', $viewsFormulaQuery);
		$viewsFromUpdate->getRowCount(); // Force la récupération des données avant réinstallation de la base
		
		// Récupère la formule des vues après script d'installation
		$this->installDatabase($version);
		$viewsFromInstall = $this->getConnection()->createQueryTable('VIEWS', $viewsFormulaQuery);
		
		$this->assertTablesEqual($viewsFromInstall, $viewsFromUpdate, 'Le code des vues est différent entre le script d\'installation et le script de mise à jour');
	}
}

DatabaseTestCase::staticInit();

?>