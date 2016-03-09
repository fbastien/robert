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
 */
// TODO PHP5.6+ : Passer à PHPUnit 5+ et trouver une autre solution que @preserveGlobalState disabled pour éviter les constantes définies en double
abstract class PageTestCase extends Database_TestCase {
	
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
	
	protected function getOutput() {
		return ob_get_contents();
	}
}
?>