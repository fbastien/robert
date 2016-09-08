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

$host = $_SERVER['HTTP_HOST'];
$serverName = php_uname('n');


define ("DEVS_MAILS", "fbastien@gmail.com");									// Adresses email des développeurs de cette branche du Robert
																				// Développeurs originaux : polo@polosson.com, mathieu@d2mphotos.fr
define ("R_VERSION", '1.0.0');

define ("TABLE_USERS",			"robert_users");								// table des utilisateurs dans la BDD
define ("TABLE_CAL"  ,			"robert_calendar");								// table du calendrier
define ("TABLE_MATOS",			"robert_matos_detail");							// table du matos au détail
define ("TABLE_MATOS_UNIT",		"robert_matos_unit");							// table du matos identifié unitairement // TODO FIXME
define ("TABLE_MATOS_CATEG",	"robert_matos_sous_cat");						// table des sous-catégories de matos
define ("TABLE_PACKS",			"robert_matos_packs");							// table des packs de matos
define ("TABLE_TEKOS",			"robert_tekos");								// table des techniciens
define ("TABLE_PLANS",			"robert_plans");								// table des plans
define ("TABLE_PLANS_DETAILS",	"robert_plans_details");						// table des details pour chaque jour de plan
define ("TABLE_STRUCT",			"robert_benef_structure");						// table des structures bénéficiaires
define ("TABLE_INTERLOC",		"robert_benef_interlocuteurs");					// table des interlocuteurs de structures bénéficiaires
define ("TABLE_DEVIS",			"robert_devis");								// table des devis
define ("TABLE_NOTES",			"robert_notes");								// table des notes de calendrier

define ("FOLDER_CLASSES",	"classes/");
define ("FOLDER_CONFIG",	"config/");
define ("FOLDER_CSS",		"css/");
define ("FOLDER_FCT",		"fct/");
define ("FOLDER_GFX",		"gfx/");
define ("FOLDER_INC",		"inc/");
define ("FOLDER_JS",		"js/");
define ("FOLDER_LIB",		"lib/");
define ("FOLDER_PAGES",		"pages/");

define ("FOLDER_TEKOS_DATA", "data/TEKOS_DATA/");
define ("FOLDER_PLANS_DATA", "data/PLANS_DATA/");
define ("FOLDER_DUMP_SQL", "data/dumpSQL/");
defined('FOLDER_IMPORT_MATOS') or define('FOLDER_IMPORT_MATOS', 'data/inventaire/');

define ("SALT_PASS",		'G:niUk5!1|WQ');									// Grain de sel (seed) pour la création / récup de password in BDD
define ("COOKIE_NAME_AUTH",	'auth_type');										// nom du cookie gestion type d'authentification
define ("COOKIE_NAME_LOG",	'auth_login');										// nom du cookie gestion login
define ("COOKIE_NAME_PASS", 'auth_password');									// nom du cookie gestion password
define ("COOKIE_NAME_TOKEN", 'token');											// nom du cookie token
define ("COOKIE_PEREMPTION", time() + (3600 * 24 * 2));							// péremption des cookies : 2 jours.

define('CONF_AUTH_DB', 'auth.email');											// Propriété de conf pour activer l'authentification par email et mot de passe en base de données
define('CONF_AUTH_LDAP', 'auth.ldap');											// Propriété de conf pour activer l'authentification par LDAP
define('CONF_LDAP_HOST', 'auth.ldap.host');										// Propriété de conf pour la connexion LDAP
define('CONF_LDAP_RDN', 'auth.ldap.read.dn');									// Propriété de conf pour la connexion LDAP
define('CONF_LDAP_PASS', 'auth.ldap.read.pass');								// Propriété de conf pour la connexion LDAP
define('CONF_LDAP_BASE', 'auth.ldap.base');										// Propriété de conf pour la connexion LDAP

define('AUTH_DB', 'DB');														// Type d'authentification par email et mot de passe dans la base de données
define('AUTH_LDAP', 'LDAP');													// Type d'authentification par LDAP

define('LDAP_DN', 'dn');														// Champ LDAP permettant d'identifier de manière unique l'utilisateur
define('LDAP_LOGIN', 'uid');													// Champ LDAP du login de l'utilisateur
define('LDAP_EMAIL', 'mail');													// Champ LDAP du login de l'utilisateur
define('LDAP_PRENOM', 'givenname');												// Champ LDAP du login de l'utilisateur
define('LDAP_NOM', 'sn');														// Champ LDAP du login de l'utilisateur

date_default_timezone_set('Europe/Paris');										// La timezone par défaut, si introuvable dans le php.ini

// Lecture du fichier de propriétés
$config = parse_ini_file($install_path . FOLDER_CONFIG . 'config.ini');
$config['boite.TVA.val'] = (float) $config['boite.TVA.val'];

defined('DSN') or define('DSN', 'mysql:dbname='.$config['db.base'].';host='.$config['db.host']);		// données de connexion à la BDD via PDO
defined('USER') or define('USER', $config['db.user']);
defined('PASS') or define('PASS', $config['db.pass']);


// fonction de suppression de dossier, vite fait, dispo pour tout le monde !
function rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
   }
 }


function coef($nbJours) {
	$c = ($nbJours - 1) * 3/4 + 1;
	return $c;
}

?>
