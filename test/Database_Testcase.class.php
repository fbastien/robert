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

abstract class Database_Testcase extends PHPUnit_Extensions_Database_TestCase {
	
	/** @var PDO only instantiate once for test clean-up/fixture load */
	static private $pdo = null;
	
	/** @var PHPUnit_Extensions_Database_DB_IDatabaseConnection only instantiate once per test */
	private $conn = null;
	
	/** @return PHPUnit_Extensions_Database_DB_IDatabaseConnection */
	final public function getConnection()
	{
		if ($this->conn === null) {
			// Les variables globales des informations de connexion sont définies dans le fichier phpunit.xml
			if (self::$pdo == null) {
				self::$pdo = new PDO( $GLOBALS['PHPUNIT_DB_DSN'], $GLOBALS['PHPUNIT_DB_USER'], $GLOBALS['PHPUNIT_DB_PASSWD'] );
				self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
			$this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['PHPUNIT_DB_DBNAME']);
		}
	
		return $this->conn;
	}
	
	/** Vide la base de données de toutes ses tables. */
	protected function truncateDatabase() {
		$pdo = $this->getConnection()->getConnection();
		$tableList = $pdo->query("SELECT `TABLE_NAME`, `TABLE_TYPE` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = '{$GLOBALS['PHPUNIT_DB_DBNAME']}'");
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
	
	/**
	 * Exécute un fichier de script SQL.
	 * 
	 * Nécessite l'extension MySQLi (car PDO ne permet pas d'exécuter plusieurs requêtes à la fois).
	 * 
	 * @param string $file Chemin du fichier à exécuter.
	 * @param boolean $isTest
	 *     Indique si cette méthode est appelée dans le cadre d'un test unitaire (true, par défaut) ou autre (setUp ou tearDown par exemple).
	 *     En cas d'erreur dans le script, dans le premier cas cela fera échouer le test, alors que sinon c'est une exception qui sera levée. @
	 * @throws RuntimeException En cas d'erreur de connexion ou pendant l'exécution du script.
	 */
	protected function executeScript($file, $isTest = true) {
		if(!extension_loaded('mysqli')) {
			throw new RuntimeException('Extension MySQLi manquante');
		}
		
		$script = mb_convert_encoding(file_get_contents($file), mb_internal_encoding(), 'UTF-8');
		
		$mysqli = new mysqli($GLOBALS['PHPUNIT_DB_HOST'], $GLOBALS['PHPUNIT_DB_USER'], $GLOBALS['PHPUNIT_DB_PASSWD'], $GLOBALS['PHPUNIT_DB_DBNAME']);
		if ($mysqli->connect_errno) {
			throw new RuntimeException("Erreur de connexion à la base de données ($mysqli->connect_errno) : $mysqli->connect_error");
		}
		
		$isSuccess = $mysqli->multi_query($script);
		
		while(true) {
			if(!$isSuccess) {
				$errMessage = "Erreur lors de l'exécution du script \"$file\" ($mysqli->errno/$mysqli->sqlstate) : $mysqli->error";
				$mysqli->close();
				if($isTest) {
					$this->fail($errMessage);
				} else {
					throw new RuntimeException($errMessage);
				}
			}
			if(!$mysqli->more_results()) {
				break;
			}
			$isSuccess = $mysqli->next_result();
		}
		$mysqli->close();
	}
}
?>