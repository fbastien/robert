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

require_once __DIR__.'/Database_TestCase.class.php';

/**
 * Classe abstraite pour les tests des pages complètes.
 * 
 * Les sous-classes doivent utiliser les annotations docblock suivantes pour fonctionner correctement :
 * * @runTestsInSeparateProcesses
 * * @preserveGlobalState disabled
 * 
 * De plus, il est conseillé qu'elles définissent une méthode setUpBeforeClass() qui appelle setTestedPage() pour préciser le fichier de la page à tester.
 * Utiliser ensuite la méthode callTestedPage() dans les différents tests.
 */
// TODO PHP5.6+ : Passer à PHPUnit 5+ et trouver une autre solution que @preserveGlobalState disabled pour éviter les constantes définies en double
abstract class PageTestCase extends Database_TestCase
{
	/** @var string Chemin (relatif à la racine du projet) du fichier de la page à tester. */
	private static $testedPage;
	
	/**
	 * @param string $testedPage Chemin (relatif à la racine du projet) du fichier de la page à tester.
	 * @see PageTestCase::callTestedFile()
	 */
	protected static function setTestedPage($testedPage) {
		self::$testedPage = dirname(__DIR__).DIRECTORY_SEPARATOR.$testedPage;
	}
	
	/** @before */
	protected function setUp() {
		parent::setUp();
		
		session_save_path(__DIR__.'/tmp');
		
		ob_start();
		
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['HTTP_USER_AGENT'] = PHPUnit_Runner_Version::getVersionString();
	}
	
	/** @after */
	protected function tearDown() {
		ob_end_clean();
		
		session_write_close();
		// TODO PHP5.4+ : Utiliser SessionHandler::gc(0) à la place
		@unlink(session_save_path().'/sess_'.session_id());
		
		parent::tearDown();
	}
	
	protected final function callTestedPage() {
		if(self::$testedPage === null) {
			throw new LogicException("setTestedPage() doit être appelée avant callTestedPage()");
		}
		require self::$testedPage;
	}
	
	protected function getOutput() {
		return ob_get_contents();
	}
}
?>