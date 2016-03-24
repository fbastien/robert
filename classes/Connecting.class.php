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

define('BF_TIME_LAPS', (60*5)); // 5 minutes		// laps de temps à attendre après avoir fait trop de tentatives (en secondes)
define('BF_NB_TENTATIVE', 5);	// 5 tentatives		// Nbre de tentatives maxi, tous les TIME_LAPS
define('BF_DIR', dirname(__DIR__).'/tmp/BFlogs/');	// répertoire de stockage des logs
if (!is_dir(BF_DIR))
	mkdir(BF_DIR);


/**
 * CLASSE DE SÉCU ANTI FORCE BRUTE
 */
class NoBF {

	public function  __construct() { }

	/** Teste si le nbre de tentative n'exède pas BF_NB_TENTATIVE dans le laps de temps défini avec BF_TIME_LAPS */
	public static function bruteCheck($login) {
		$filename = BF_DIR . $login . '.tmp';
		$deny_access = false;

		if (file_exists($filename)) {
			$infos = NoBF::fileToArray($filename);
			$nb_tentatives = count($infos);
			$premiere_tentative = @$infos[0];

			if ($nb_tentatives < BF_NB_TENTATIVE)
				$deny_access = false;
			elseif ($nb_tentatives > BF_NB_TENTATIVE && (BF_TIME_LAPS + $premiere_tentative) > time())
				$deny_access = true;
			else
				$deny_access = false;
		}
		return $deny_access;
	}

	public static function addTentative($login) {
		$filename = BF_DIR . $login . '.tmp';
		$date = time();

		if(file_exists($filename))
			$infos = NoBF::fileToArray($filename);
		else $infos = array();

		$infos[] = $date;
		NoBF::arrayToFile($filename, $infos);
	}

	/** Permet de supprimer les enregistrements trop anciens */
	public static function cleanUp($infos) {
		foreach($infos as $n => $date) {
			if((BF_TIME_LAPS + $date) < time())
				unset($infos[$n]);
		}
		return array_values($infos);
	}

	/** Récupère les infos du fichier et les retourne unserialisé */
	public static function fileToArray($filename) {
		$infos = unserialize( file_get_contents($filename) );
		$infos = NoBF::cleanUp($infos);
		return $infos;
	}

	/** Enregistre les infos dans le fichier de log serialisé */
	public static function arrayToFile($filename, $data) {
		$file = fopen ($filename, "w");
		fwrite($file, serialize($data) );
		fclose ($file);
		return true;
	}
}


/**
 * CLASSE DE CONNEXION D'UN UTILISATEUR
 */
class Connecting {

	private $db; // Instance de PDO
	private $connected;
	private $user = array();

	public function __construct($db) {
		$this->db = $db;
		if($this->testConnexion() == false) {
			$this->connected = false;
		}
	}

	/** Retourne si cette personne est connectée ou pas */
	public function is_connected() {
		if ($this->connected)
			return $_SESSION[COOKIE_NAME_LOG];
		else return false;
	}

	/**
	 * Connexion
	 * @param string $login email ou identifiant LDAP
	 * @param string $password mot de passe non crypté
	 */
	public function connect($login, $password) {
		$deny_login = NoBF::bruteCheck($login);
		$this->disconnect();
		$login = preg_replace('/\\\'/', '', $login); // Empêcher les injections SQL en virant les '

		if ($deny_login == true)
			exit('Trop de tentatives de connexion. Merci de recommencer dans quelques minutes.');
		else {
			try {
				$isAuthValid = $this->authenticate($login, $password);
				if ($isAuthValid) {
					$this->connected = true;
					$this->setSecuredData();
					$this->updateUser($this->user['id'], 1);
					return true;
				}
			}
			catch (Exception $e) {
				echo $e->getMessage();
				// die();
			}
			
			NoBF::addTentative($login);
			return false;
		}
	}


	/** Déconnexion */
	public function disconnect() {
		$this->resetSessionData();
		session_unset();
	}

	/** Teste la connexion en cours */
	private function testConnexion() {
		// def des vars à tester
		$toTestAuth = '';
		$toTestLogin = '';
		$toTestPassword = '';
		$toTestToken = '';

		// Conservation d'une connexion via cookie
		if (isset($_COOKIE[COOKIE_NAME_AUTH]) && !empty($_COOKIE[COOKIE_NAME_LOG]) && !empty($_COOKIE[COOKIE_NAME_PASS]) && empty($_SESSION[COOKIE_NAME_PASS])) {
			$toTestAuth = $_COOKIE[COOKIE_NAME_AUTH];
			$toTestLogin = $_COOKIE[COOKIE_NAME_LOG];
			$toTestPassword = $_COOKIE[COOKIE_NAME_PASS];
			$toTestToken = $_COOKIE[COOKIE_NAME_TOKEN];
		} elseif (isset($_SESSION[COOKIE_NAME_AUTH]) && !empty($_SESSION[COOKIE_NAME_LOG]) && !empty($_SESSION[COOKIE_NAME_PASS])) {
			$toTestAuth = $_SESSION[COOKIE_NAME_AUTH];
			$toTestLogin = $_SESSION[COOKIE_NAME_LOG];
			$toTestPassword = $_SESSION[COOKIE_NAME_PASS];
			$toTestToken = $_SESSION[COOKIE_NAME_TOKEN];
		}

		// Si le token n'est pas identique au fingerprint du navigateur, on reset tout
		if($toTestToken != $this->fingerprint()) {
			$this->resetSessionData();
			return false;
		}

		if (!empty($toTestAuth) && !empty($toTestLogin) && !empty($toTestPassword)) {
			// teste si l'utilisateur existe bel et bien
			$isAuthValid = $this->authenticate($toTestLogin, $toTestPassword, true);
			if ($isAuthValid) {
				$this->connected = true;

				// Si connexion depuis cookie : on remet en place les sessions + cookies
				if (!empty($_SESSION[COOKIE_NAME_LOG]) || empty($_SESSION[COOKIE_NAME_PASS]))
					$this->setSecuredData();

				$this->updateUser($this->user['id']);
				return true;
			}
			else {
				$this->resetSessionData();
				return false;
			}
		}
		else return false;
	}
	
	/**
	 * Vérifie les identifiants de connexion de l'utilisateur (soit directement dans la base de données si c'est avec un email, soit auprès de LDAP).
	 * Met aussi à jour les informations dans $this->user.
	 * 
	 * @param string $login Identifiant de l'utilisateur (email ou uid LDAP)
	 * @param string $password Mot de passe de l'utilisateur
	 * @param bool $isPwdHashed Indique si le mot de passe a déjà été hashé (pour stockage en base de données)
	 * @return bool true si les identifiants sont corrects, false si l'utilisateur n'existe pas ou si le mot de passe est erroné
	 * @throws Exception en cas d'erreur technique sur la base de données ou le serveur LDAP
	 */
	private function authenticate($login, $password, $isPwdHashed = false) {
		global $config;
		
		if ($config[CONF_AUTH_DB] && $config[CONF_AUTH_LDAP])
			$isLoginEmail = (filter_var($login, FILTER_VALIDATE_EMAIL) !== false);
		elseif ($config[CONF_AUTH_DB])
			$isLoginEmail = true;
		elseif ($config[CONF_AUTH_LDAP])
			$isLoginEmail = false;
		else
			return false;
		
		$q = $this->db->prepare("SELECT `id`, `ldap_uid`, `email`, `password`
									FROM `".TABLE_USERS."`
									WHERE `".($isLoginEmail ? 'email' : 'ldap_uid')."` = '$login'");
		$q->execute();
		
		if ($q->rowCount() == 1) {
			$this->user = $q->fetch(PDO::FETCH_ASSOC);
			
			// Authentification par email et mot de passe
			if ($isLoginEmail) {
				$pwdToCompare = ($isPwdHashed ? $password : md5(SALT_PASS.$password));
				return ($this->user['password'] === $pwdToCompare);
			}
			// Authentification par LDAP
			else {
				// TODO Déplacer dans une classe dédiée à LDAP
				// Le mot de passe ne doit pas être vide pour ne pas être considéré comme une connexion LDAP anonyme
				if ($password === '') {
					return false;
				}
				
				$ldap = ldap_connect($config[CONF_LDAP_HOST]);
				if (! $ldap) {
					throw new Exception("Erreur de connexion LDAP");
				}
				
				if(! ldap_bind($ldap, $config[CONF_LDAP_RDN], $config[CONF_LDAP_PASS])) {
					ldap_unbind($ldap);
					throw new Exception("Erreur LDAP : ".@ldap_error($ldap));
				}
				// Vérification que l'utilisateur existe dans LDAP et récupération de son DN
				$ldap_result = ldap_search($ldap, $config[CONF_LDAP_BASE], "(".LDAP_LOGIN."=$login)", array(LDAP_DN));
				if (! $ldap_result) {
					ldap_unbind($ldap);
					throw new Exception("Erreur LDAP : ".ldap_error($ldap), ldap_errno($ldap));
				}
				$ldap_data = ldap_get_entries($ldap, $ldap_result);
				if ($ldap_data['count'] == 1) {
					// Test de connexion avec le DN récupéré et le mot de passe fourni
					if(@ldap_bind($ldap, $ldap_data[0][LDAP_DN], $password)) {
						ldap_unbind($ldap);
						$this->user['password'] = $password;
						return true;
					}
				}
				ldap_unbind($ldap);
				return false;
			}
		}
		return false;
	}

	/** Génère le token (jeton) du navigateur en cours */
	private function fingerprint() {
		$fingerprint = SALT_PASS . $_SERVER['HTTP_USER_AGENT'];
		$token = md5($fingerprint . session_id());

		return $token;
	}

	/** On défini les variables d'identifications (auth type, login, mot de passe et token) !! obligation d'avoir défini $this->user avant de l'utiliser !! */
	private function setSecuredData() {
		if (empty($this->user['ldap_uid'])) {
			$authType = AUTH_DB;
			$login = $this->user['email'];
		} else {
			$authType = AUTH_LDAP;
			$login = $this->user['ldap_uid'];
		}
		$token = $this->fingerprint();
		
		// declaration des sessions
		$_SESSION[COOKIE_NAME_AUTH] = $authType;
		$_SESSION[COOKIE_NAME_LOG] = $login;
		$_SESSION[COOKIE_NAME_PASS] = $this->user['password'];
		$_SESSION[COOKIE_NAME_TOKEN] = $token;

		// déclaration des cookies
		setcookie(COOKIE_NAME_AUTH, $authType, COOKIE_PEREMPTION, "/");
		setcookie(COOKIE_NAME_LOG, $login, COOKIE_PEREMPTION, "/");
		setcookie(COOKIE_NAME_PASS, $this->user['password'], COOKIE_PEREMPTION, "/");
		setcookie(COOKIE_NAME_TOKEN, $token, COOKIE_PEREMPTION, "/");
	}

	/** Reset complet des variables d'identification... C'est une déconnexion ! */
	private function resetSessionData() {
		// declaration des sessions
		$_SESSION[COOKIE_NAME_AUTH] = '';
		$_SESSION[COOKIE_NAME_LOG] = '';
		$_SESSION[COOKIE_NAME_PASS] = '';
		$_SESSION[COOKIE_NAME_TOKEN] = '';

		// destruction des cookies en leur mettant une expiration dans le passé
		$peremptionCookies = time() - (3600 * 24 * 31 * 365); // - 1 an
		setcookie(COOKIE_NAME_AUTH, '', $peremptionCookies, "/");
		setcookie(COOKIE_NAME_LOG, '', $peremptionCookies, "/");
		setcookie(COOKIE_NAME_PASS, '', $peremptionCookies, "/");
		setcookie(COOKIE_NAME_TOKEN, '', $peremptionCookies, "/");

		$this->connected = false;
		$this->user = array();
		session_unset();
	}

	/**
	 * Mise à jour de divers infos de connexion dans la BDD
	 * @param int $id id du user
	 * @param int $connexion 0 = test de connexion, 1 = connexion
	 */
	private function updateUser($id, $connexion = 0) {
		$date = time();
		$addReq = ($connexion == 1) ? ", `date_last_connexion` = '$date'" : "";
		$q = $this->db->prepare("UPDATE ".TABLE_USERS." SET `date_last_action` = '$date'$addReq WHERE `id` = '$id'");
		$q->execute();
	}
}

?>
