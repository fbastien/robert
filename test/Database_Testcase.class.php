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
	
	// only instantiate pdo once for test clean-up/fixture load
	static private $pdo = null;
	
	// only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
	private $conn = null;
	
	final public function getConnection()
	{
		if ($this->conn === null) {
			// Les variables globales des informations de connexion sont définies dans le fichier phpunit.xml
			if (self::$pdo == null) {
				self::$pdo = new PDO( $GLOBALS['PHPUNIT_DB_DSN'], $GLOBALS['PHPUNIT_DB_USER'], $GLOBALS['PHPUNIT_DB_PASSWD'] );
			}
			$this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['PHPUNIT_DB_DBNAME']);
		}
	
		return $this->conn;
	}
}
?>