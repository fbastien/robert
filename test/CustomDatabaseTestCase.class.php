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

require_once __DIR__.DIRECTORY_SEPARATOR.'DatabaseTestCase.class.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'Version.class.php';

/**
 * Test utilisant la base de données dans une configuration particulière (différente de celle mise en place par défaut par DatabaseTestCase).
 * 
 * Par défaut, les tests héritant de cette classe :
 * * ne modifient pas l'état existant de la base de données lors de l'initialisation de la classe (au lieu de l'installer dans la dernière version),
 * * commençent avec les tables (et un DataSet) vides.
 */
abstract class CustomDatabaseTestCase extends DatabaseTestCase {
	
	/**
	 * {@inheritDoc}
	 * 
	 * Contrairement à DatabaseTestCase, ce constructeur laisse la base de données telle quelle, il n'installe pas la dernière version.
	 * C'est donc aux sous-classes que revient la responsabilité de mettre la base de données dans la bonne configuration.
	 * 
	 * @see DatabaseTestCase::installDatabase()
	 */
	public function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName, false);
	}
	
	/**
	 * Ne fait rien à l'initialisation de la classe (contrairement à la méthode de la classe mère qui installe la base de données).
	 * 
	 * @see PHPUnit_Framework_TestCase::setUpBeforeClass()
	 */
	public static function setUpBeforeClass() {}
	
	/**
	 * Retourne un dataset vide pour l'initialisation des tests.
	 * 
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	public function getDataSet() {
		return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
	}
}
?>