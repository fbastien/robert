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

class MatosUnit implements Iterator {

	/** erreur si une donnée ne correspond pas */
	const UPDATE_ERROR_DATA = 'donnée invalide' ;
	/** message si une modif BDD a réussi */
	const UPDATE_OK			= 'donnée modifiée, OK !' ;
	/** erreur de la méthode Infos::loadInfos() */
	const INFO_ERREUR		= 'Impossible de lire les infos en BDD';
	/** erreur si champs inexistant dans BDD lors de récup ou update d'info */
	const INFO_DONT_EXIST	= 'donnée inexistante' ;
	/** erreur si info est une donnée sensible */
	const INFO_FORBIDDEN	= 'donnée interdite' ;
	/** erreur si la référence du matos unitaire n'est pas renseignée au __construct */
	const REF_MANQUE		= 'Il manque la référence !';
	/** erreur si il manque des infos lors de la sauvegarde */
	const MANQUE_INFO		= 'Pas assez d\'info pour sauvegarder';
	
	/** retour général, si la fonction a marché */
	const MATOS_OK          = true ;
	/** retour général, si la fonction n'a pas marché */
	const MATOS_ERROR       = false ;
	
	/** champ BDD où trouver la référence du matos unitaire */
	const REF_MATOS_UNIT	= 'ref';
	/** champ BDD où trouver l'id du matos unitaire */
	const ID_MATOS_UNIT		= 'id_matosunit';
	
	/** @var Infos Gestion en BDD des informations du matos unitaire. */
	private $infos;
	/** @var int|string ID (ou autre champ BDD) du matos à construire. */
	private $id;
	/** @var array Sauvegarde des données lors de l'appel au constructeur pour déterminer celles à mettre à jour. */
	private $baseInfo;
	
	public function __construct ($champ='new', $id='') {
		// Création de l'instance de 'Infos'
		$this->infos = new Infos( TABLE_MATOS_UNIT );
		if ( $champ == 'new' )
			return;
		if ( $id == '' )
			throw new Exception(MatosUnit::REF_MANQUE);
		$this->id = $id;
		// Récupération des données en BDD
		$this->loadFromBD( $champ, $this->id );
	}
	
	public function loadFromBD ( $keyFilter , $value ) {
		try {
			$this->infos->loadInfos( $keyFilter, $value );
			$this->baseInfo = $this->infos->getInfo();
		}
		catch (Exception $e) {
			throw new Exception(MatosUnit::INFO_ERREUR);
		}
	}
	
	public function getExterne() {
		return $this->infos->getInfo('ownerExt') === null ? '0' : '1';
	}
	
	public function getMatosUnitInfos ($what='') {
		if ($what == '') {
			// Récup toutes les infos
			try {
				$info = $this->infos->getInfo();
				$info['externe'] = $this->getExterne();
			}
			catch (Exception $e) {
				return $e->getMessage();
			}
		}
		else {
			// Récup une seule info
			try {
				if($what === 'externe') {
					$info = $this->getExterne();
				} else {
					$info = $this->infos->getInfo($what);
				}
			}
			// Si existe pas, récup de l'erreur
			catch (Exception $e) {
				return $e->getMessage();
			}
		}
		return $info;
	}
	
	/** (Re)définit les infos du matos unitaire */
	public function setVals ($arrKeysVals) {
		// Gestion des champs avec une valeur particulière
		if(isset($arrKeysVals['externe'])) {
			if($arrKeysVals['externe'] === '1') {
				$arrKeysVals['dateAchat'] = null;
			}
			elseif($arrKeysVals['externe'] === '0') {
				$arrKeysVals['ownerExt'] = null;
			}
			unset($arrKeysVals['externe']);
		}
		// Gestion des champs qui peuvent être NULL
		foreach (array('dateAchat', 'ownerExt', 'remarque') as $champ) {
			if($arrKeysVals[$champ] === '') {
				$arrKeysVals[$champ] = null;
			}
		}
		
		foreach ($arrKeysVals as $key => $val)
			$this->infos->addInfo($key, $val);
	}
	
	public function updateMatosUnit ($typeInfo = false, $newInfo = false) {
		// Si on spécifie une clé/valeur, on update que celle-ci
		if ($typeInfo !== false && $newInfo !== false) {
			try {
				$this->infos->update($typeInfo, $newInfo);
				return "Mise à jour de $typeInfo effectuée !";
			}
			catch (Exception $e) {
				return $e->getMessage();
			}
		}
		// Sinon, on compare les nouvelles valeurs avec les anciennes
		else {
			$retour = '';
			$newInfos = $this->infos->getInfo();
			$diffInfos = array_diff_assoc($newInfos, $this->baseInfo); // retourne un tableau ne contenant que la différence
			foreach ($diffInfos as $key => $val) {
				// effectue l'update seulement pour les champs qui sont différents, sauf pour ID qui est en auto-increment
				try {
					$this->infos->update($key, $val);
					$retour .= "Mise à jour de $key effectuée !<br />";
				}
				catch (Exception $e) {
					return $e->getMessage();
				}
			}
			return $retour;
		}
	}
	
	/** Sauvegarde d'un nouveau matos unitaire */
	public function save () {
		$verifInfo = $this->infos->getInfo();
		// Check si on a bien tout ce qu'il faut avant de sauvegarder en BDD
		if ( !$verifInfo['ref'] )
			throw new Exception(MatosUnit::MANQUE_INFO);
		
		$this->infos->save();
		return MatosUnit::MATOS_OK ;
	}
	
	public function deleteMatosUnit () {
		$nb = $this->infos->delete(MatosUnit::ID_MATOS_UNIT, $this->id);
		return $nb ;
	}
	
	/** @see Iterator::current() */
	public function current() { return $this->infos->current(); }
	/** @see Iterator::next() */
	public function next() { $this->infos->next(); }
	/** @see Iterator::rewind() */
	public function rewind() { $this->infos->rewind(); }
	/** @see Iterator::valid() */
	public function valid() { return $this->infos->valid(); }
	/** @see Iterator::key() */
	public function key() { return $this->infos->key(); }
}
?>