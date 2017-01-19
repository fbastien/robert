<?php

function creerSousCatArray ($liste_Matos = null) {
	if ($liste_Matos == null) return false ;
	
	$sousCategList = array();
	foreach ( $liste_Matos as $matos ){

		if ( ! isset ( $matos['sousCateg']) ) continue ;
		$sousCategList[$matos['sousCateg']][] = $matos ;
	}
	
	if ( empty ($sousCategList)  ) return false ; 
	return $sousCategList;
}


/** Crée une sous-catégorie supplémentaire nommée contenant le materiel a louer */
function creerSousCatArray_showExterieur ($liste_Matos = null ) {
	if ($liste_Matos == null) return false ;

	$sousCategList = array();
	$ext = array(); 
	foreach ( $liste_Matos as $matos ){

		if ( ! isset ( $matos['sousCateg']) ) continue ;

		if ( $matos['ownerExt'] !== null) {
			$ext[] = $matos;
		}
		else
			$sousCategList[$matos['sousCateg']][] = $matos ;
	}
	
	if ( empty ($sousCategList) && empty($ext) ) return false ;
	$sousCategList[999] = $ext;
	return $sousCategList;
}



function simplifySousCatArray($liste_sousCat = null) {
	
	if ($liste_sousCat == null) {
		$ls = new Liste();
		$liste_sousCat = $ls->getListe(TABLE_MATOS_CATEG, '*', 'ordre', 'ASC');
		unset($ls);
	}
	array_push($liste_sousCat, array ( 'id'=> 0, 'label'=> 'sans sous catégorie' ));
	array_push($liste_sousCat, array ( 'id' => 999, 'label' => 'A louer' ));
	
	$newTableau = array();
	foreach( $liste_sousCat as $ssCat){
		$ind = $ssCat['id'];
		$newTableau[$ind] = $ssCat ;
	}
	return $newTableau ;
}


/** Trie le tableau de matériel extérieur par le champ 'ownerExt' ! */
function MatosExt_by_Location ( $listeMatosExterieur ){

	$newTableau = array();
	foreach ( $listeMatosExterieur as $matos ){
		$newTableau[$matos['ownerExt']][] = $matos ;
	}
	return $newTableau ; 
}

/** 
 * Regroupe le matériel identifié unitairement en fonction du matériel générique auquel il correspond.
 * 
 * @param mixed[][] $listeMatosUnit Liste du matériel unitaire.
 *     Chaque élément de la première dimension du tableau correspond à un matériel.
 *     La seconde dimension est un tableau associatif contenant les informations du matériel contenues en base de données.
 * @return mixed[][][] Tableau qui associe à chaque identifiant de matériel générique (clé de la première dimension) la liste des matériels unitaires associés (format des 2 autres dimensions identique au tableau en entrée).
 */
function groupUnitsByMatos($listeMatosUnit) {
	$newTableau = array();
	foreach ($listeMatosUnit as $matosUnit){
		$newTableau[$matosUnit['id_matosdetail']][] = $matosUnit;
	}
	return $newTableau;
}
