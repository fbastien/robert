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

/**
 * Classe abstraite pour les tests des pages complètes.
 * 
 * Il est conseillé que les sous-classes définissent une méthode setUpBeforeClass() qui appelle setTestedPage() pour préciser le fichier de la page à tester.
 * Utiliser ensuite la méthode callTestedPage() à l'intérieur de chaque méthode de test pour appeler la page.
 * 
 * De plus, lorsque les sous-classes définissent setUpBeforeClass() ou tearDownAfterClass() (ou utilisent les annotations beforeClass et afterClass respectivement),
 * elles doivent inclure l'appel à la méthode correspondante dans cette classe (utiliser le mot-clé parent::).
 * 
 * Note : Cette classe active sur toutes les sous-classes l'équivalent des annotations suivantes :
 * * @runTestsInSeparateProcesses
 * * @preserveGlobalState disabled
 */
// TODO PHP5.6+ : Passer à PHPUnit 5+ et trouver une autre solution que @preserveGlobalState disabled pour éviter les constantes définies en double
abstract class PageTestCase extends DatabaseTestCase
{
	/** @var string[] Liste des variables qui n'ont pas besoin d'êtres reportées dans le contexte global lors des inclusions de fichiers dans des fonctions. */
	private static $IGNORED_GLOBAL_VARS = array('GLOBALS', '_SERVER', '_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_REQUEST', '_ENV', '__PHPUNIT_CONFIGURATION_FILE', '__PHPUNIT_BOOTSTRAP', '__PageTestCase_varName', '__PageTestCase_file');
	/** @var string Chemin (relatif à la racine du projet) du fichier de la page à tester. */
	private static $testedPage;
	/** @var string[] Nom des variables globales créées dans la page à tester et accédées dans ses fonctions. */
	private static $globalVarNames = array();
	
	/**
	 * {@inheritDoc}
	 *
	 * Ce constructeur active l'isolation des tests dans des processus séparés et désactive la préservation des variables globales.
	 */
	public function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	
		$this->setRunTestInSeparateProcess(true);
		$this->setPreserveGlobalState(false);
	}
	
	/**
	 * Renseigne la page à tester par ce cas de test.
	 * 
	 * @param string $testedPage Chemin (relatif à la racine du projet) du fichier de la page à tester.
	 * @param string[] $globalVarNames Nom des variables globales créées dans le fichier à tester et qui doivent rester globales car elles sont accédées depuis ses fonctions.
	 *     Les variables qui ne sont pas indiquées dans cette liste deviendront locales à la fonction callTestedPage() et ne seront donc plus accessibles avec le mot-clé global.
	 *     Les variables déjà définies globales autre part ou importées avec requireOnce() n'ont pas besoin d'être indiquées dans cette liste pour rester accessibles.
	 * @see PageTestCase::callTestedFile()
	 */
	protected static function setTestedPage($testedPage, array $globalVarNames = array()) {
		self::$testedPage = dirname(__DIR__).DIRECTORY_SEPARATOR.$testedPage;
		self::$globalVarNames = $globalVarNames;
	}
	
	/**
	 * Inclut un fichier (comme require_once) tout en conservant les variables dans le scope global au lieu de la fonction qui l'appelle.
	 *
	 * @param string $file Nom (ou chemin) du fichier à inclure.
	 */
	protected static function requireOnce($__PageTestCase_file) {
		// Inclusion de toutes les variables globales dans le scope de cette fonction afin qu'elles soient accessibles depuis le fichier inclus
		foreach(array_diff(array_keys($GLOBALS), self::$IGNORED_GLOBAL_VARS) as $__PageTestCase_varName) {
			// Le nom de la variable de boucle et du paramètre de la fonction doivent être dans le tableau $IGNORED_GLOBAL_VARS pour qu'ils ne deviennent pas globaux !
			global ${$__PageTestCase_varName};
		}
		unset($__PageTestCase_varName);
		// Inclusion du fichier
		require_once $__PageTestCase_file;
		unset($__PageTestCase_file);
		// Sauvegarde dans le scope global de toutes les variables définies dans le corps principal du fichier inclus
		// pour qu'elles ne restent pas dans le scope de cette fonction mais qu'elles soient globales comme c'est le cas normalement
		foreach(array_diff_key(get_defined_vars(), $GLOBALS) as $varName => $varValue) {
			$GLOBALS[$varName] = $varValue;
		}
	}
	
	/**
	 * {@inheritDoc}
	 *
	 * Si une sous-classe définit setUpBeforeClass() ou utilise l'annotation beforeClass, elle doit inclure l'appel à cette méthode.
	 *
	 * Cette méthode s'assure que l'installation de la base de données n'est effectuée qu'une seule fois pour toute la classe,
	 * et non une fois par méthode de test à cause de l'isolation en processus séparés.
	 *
	 * @see PHPUnit_Framework_TestCase::setUpBeforeClass()
	 */
	public static function setUpBeforeClass() {
		// TODO PHP 5.6+ : Changer en constante de classe
		defined('LOCK_FILE') or define('LOCK_FILE', __DIR__.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.__CLASS__.'.lock');
		
		/* Lorsque les tests sont exécutés dans des processus séparés,
		 * la méthode setUpBeforeClass() est appelée une première fois dans le processus principal,
		 * puis elle est appelée à nouveau dans chaque processus fils (et donc avant chaque méthode de test).
		 * Dans le processus principal de PHPUnit, on crée un fichier de verrou avec le PID.
		 * Si le fichier existe, c'est qu'on est dans un processus fils et on n'initialise pas la base de données.
		 */
		if(! file_exists(LOCK_FILE)) {
			if(file_put_contents(LOCK_FILE, getmypid()) === false)
				throw new RuntimeException("Impossible de créer le fichier de verrou pour les tests isolés");
				// Installation de la base de données
				parent::setUpBeforeClass();
		}
	}
	
	/**
	 * {@inheritDoc}
	 *
	 * Si une sous-classe définit tearDownAfterClass() ou utilise l'annotation afterClass, elle doit inclure l'appel à cette méthode.
	 *
	 * @see PHPUnit_Framework_TestCase::tearDownAfterClass()
	 */
	public static function tearDownAfterClass() {
		/* Suppression du verrou créé dans setUpBeforeClass().
		 * Lorsque les tests sont exécutés dans des processus séparés,
		 * la méthode tearDownAfterClass() est appelée dans chaque processus fils (et donc après chaque méthode de test),
		 * puis elle est appelée une dernière fois dans le processus principal.
		 * On vérifie donc le PID dans le fichier de verrou, et si c'est un PID différent du processus courant, on est dans un processus fils.
		 * En revanche, si c'est le même PID, c'est que tous les processus fils des différents tests ont été exécutés et on peut le supprimer.
		 */
		if(file_get_contents(LOCK_FILE) === (string) getmypid()) {
			unlink(LOCK_FILE);
		}
	
		parent::tearDownAfterClass();
	}
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		
		session_save_path(__DIR__.DIRECTORY_SEPARATOR.'tmp');
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['HTTP_USER_AGENT'] = PHPUnit_Runner_Version::getVersionString();
		
		ob_start();
	}
	
	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown() {
		ob_end_clean();
		
		$this->sessionDestroy();
		
		parent::tearDown();
	}
	
	/**
	 * Appelle la page à tester.
	 * 
	 * @throws LogicException Si la page à tester n'a pas été renseignée auparavant.
	 * @see PageTestCase::setTestedPage()
	 */
	protected final function callTestedPage() {
		// Vérification que la page est renseignée
		if(self::$testedPage === null) {
			throw new LogicException("setTestedPage() doit être appelée avant callTestedPage()");
		}
		// Inclusion de toutes les variables globales dans le scope de cette fonction afin qu'elles soient accessibles depuis le fichier appelé
		foreach(array_diff(array_keys($GLOBALS), self::$IGNORED_GLOBAL_VARS) as $__PageTestCase_varName) {
			// Le nom de la variable de boucle doit être dans le tableau $IGNORED_GLOBAL_VARS pour qu'elle ne devienne pas globale !
			global ${$__PageTestCase_varName};
		}
		foreach(self::$globalVarNames as $__PageTestCase_varName) {
			global ${$__PageTestCase_varName};
		}
		unset($__PageTestCase_varName);
		// Appel de la page
		require self::$testedPage;
	}
	
	/** Connecte en session un utilisateur ayant les droits d'administration. */
	protected function sessionConnectAdmin() {
		self::requireOnce(dirname(__DIR__).DIRECTORY_SEPARATOR.'initInclude.php');
		self::requireOnce('common.inc.php');
		
		session_start();
		$Auth = new Connecting($this->getConnection()->getConnection());
		$Auth->connect('root@robertmanager.org', 'admin');
		session_write_close();
	}
	
	/** Ferme et détruit la session courante */
	protected function sessionDestroy() {
		if(! empty(session_id())) {
			// Réouverture de la session si elle a été fermée
			// TODO PHP5.4+ : Inclure dans une condition session_status() === PHP_SESSION_NONE
			@session_start();
			// Destruction des variables de session
			$_SESSION = array();
			// Effacement du cookie de session
			if (ini_get('session.use_cookies')) {
				$params = session_get_cookie_params();
				setcookie(session_name(), '', 1, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
			}
			// Destruction de la session
			session_destroy();
		}
	}
	
	protected function getOutput() {
		return ob_get_contents();
	}
}
?>