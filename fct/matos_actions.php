<?php
session_start();
require_once ('initInclude.php');
require_once (dirname(__DIR__).'/inc/common.inc.php');		// OBLIGATOIRE pour les sessions, à placer TOUJOURS EN HAUT du code !!
require_once (dirname(__DIR__).'/inc/checkConnect.php' );

extract($_POST);


if ( $action == 'select') {
	// Récupération des infos générales du matériel
	$matos = new Matos ('id', $id);
	$retour = $matos->getMatosInfos();
	
	// Récupération des infos du matériel identifié unitairement
	$retour['units'] = array();
	$l = new Liste();
	$listeUnits = $l->getListe(TABLE_MATOS_UNIT, MatosUnit::ID_MATOS_UNIT, MatosUnit::ID_MATOS_UNIT, '', MatosUnit::ID_MATOS_DETAIL, '=', $id);
	if (is_array($listeUnits)) {
		foreach ($listeUnits as $idUnit) {
			$unit = new MatosUnit(MatosUnit::ID_MATOS_UNIT, $idUnit);
			$retour['units'][] = $unit->getMatosUnitInfos();
		}
	}
	
	$retour = json_encode($retour);
	echo $retour ;
}

if ( $action == 'addMatos') {
	if ($label == '' || $ref == '' || $Qtotale == '' || $tarifLoc == '' || $valRemp == '') {
		echo 'Pas assez de données... ';
		return;
	}
	unset($_POST['action']);

	$tmpMatos = new Matos();
	unset($_POST['matosUnits']);
	$tmpMatos->setVals($_POST);
	// TODO Vérifier que la référence n'existe pas déjà dans la table robert_matos_unit
	
	try {
		if ( $tmpMatos->save() )
			echo "Matériel $ref ajouté !";
		$tmpMatos->loadFromBD(Matos::REF_MATOS, $ref);
		$idMatos = $tmpMatos->getMatosInfos('id');
		
		if (isset($matosUnits)) {
			foreach ($matosUnits as $unit) {
				$tmpUnit = new MatosUnit();
				$unit[MatosUnit::ID_MATOS_DETAIL] = $idMatos;
				$tmpUnit->setVals($unit);
				// TODO Vérifier que la référence n'existe pas déjà dans la table robert_matos_detail
				
				try {
					if ( $tmpUnit->save() )
						echo "<br />Matériel unitaire {$unit['ref']} ajouté !";
				} catch (Exception $e) {
					echo '<br />'.$e->getMessage();
				}
			}
		}
	}
	catch (Exception $e) {
		echo $e->getMessage();
	}
	unset($tmpMatos);
	unset($tmpUnit);
	unset($matosUnits);
}

if ( $action == 'addMatosJson') {
	if ($label == '' || $ref == '' || $Qtotale == '' || $tarifLoc == '' || $valRemp == '') {
		$result['success'] = 'Pas assez de données... ';
		return;
	}
	unset($_POST['action']);
	$tmpMatos = new Matos ();
	$tmpMatos->setVals ($_POST);
	
	try {
		if ( $tmpMatos->save() )
			$result['success'] = 'SUCCESS';
	} catch (Exception $e) {
		$result['success'] = $e->getMessage();
	}
	unset ($tmpMatos);
	
	if($result['success'] === 'SUCCESS') {
		$tmpMatos = new Matos ();
		$tmpMatos->loadFromBD( 'ref' , $ref ); 
		foreach ($tmpMatos as $k => $v ){
			$result['matos'][$k] = $v ;
		}
		$result['matos']['externe'] = $tmpMatos->getExterne();
	}
	echo json_encode($result);
}

if ( $action == 'modif') {
	if ($label == '' || $ref == '' || $Qtotale == '' || $tarifLoc == '' || $valRemp == '') {
		echo 'Pas assez de données... ';
		return;
	}
	unset($_POST['action']);
	unset($_POST['id']);
	
	$modMatos = new Matos('id', $id);
	unset($_POST['matosUnits']);
	$modMatos->setVals($_POST);
	// TODO Vérifier que la référence n'existe pas déjà dans la table robert_matos_unit
	
	try {
		if ($modMatos->save())
			echo 'Matériel sauvegardé !';
		else
			echo 'Impossible de sauvegarder.';
		
		if (isset($matosUnits)) {
			foreach ($matosUnits as $unit) {
				$unitAction = $unit['action'];
				$unitId = isset($unit['id']) ? $unit['id'] : -1;
				unset($unit['action']);
				unset($unit['id']);
				
				if($unitAction == 'ADD') {
					$tmpUnit = new MatosUnit();
					$unit[MatosUnit::ID_MATOS_DETAIL] = $id;
					$tmpUnit->setVals($unit);
					// TODO Vérifier que la référence n'existe pas déjà dans la table robert_matos_detail
					
					try {
						if ( $tmpUnit->save() )
							echo "<br />Matériel unitaire {$unit['ref']} ajouté !";
					} catch (Exception $e) {
						echo "<br />Impossible d'ajouter le matériel unitaire {$unit['ref']} : ".$e->getMessage();
					}
				}
				
				elseif($unitAction == 'MOD') {
					$modUnit = new MatosUnit(MatosUnit::ID_MATOS_UNIT, $unitId);
					$modUnit->setVals($unit);
					// TODO Vérifier que la référence n'existe pas déjà dans la table robert_matos_unit
					
					try {
						if ($modUnit->save())
							echo "<br />Matériel unitaire {$unit['ref']} sauvegardé !";
					} catch (Exception $e) {
						echo "<br />Impossible de sauvegarder le matériel unitaire {$unit['ref']} : ".$e->getMessage();
					}
				}
				
				elseif($unitAction == 'DEL') {
					try {
						$delUnit = new MatosUnit(MatosUnit::ID_MATOS_UNIT, $unitId);
						$delUnitInfos = $delUnit->getMatosUnitInfos();
						if ($delUnit->deleteMatosUnit() > 0) {
							echo "<br />Matériel unitaire {$delUnitInfos['ref']} supprimé !";
						}
						else {
							echo "<br />Impossible de supprimer le matériel unitaire {$delUnitInfos['ref']}...";
						}
					}
					catch(Exception $e) {
						echo '<br />Impossible de supprimer le matériel unitaire : '.$e->getMessage();
					}
				}
			}
		}
	} catch (Exception $e) {
		echo $e->getMessage();
	}
}

if ( $action == 'delete') {
	ini_set('display_errors', false);
	try {
		$l = new Liste();
		$listeDelUnits = $l->getListe(TABLE_MATOS_UNIT, MatosUnit::ID_MATOS_UNIT, MatosUnit::ID_MATOS_UNIT, '', MatosUnit::ID_MATOS_DETAIL, '=', $id);
		if (is_array($listeDelUnits)) {
			foreach ($listeDelUnits as $idUnitDel) {
				$delUnit = new MatosUnit(MatosUnit::ID_MATOS_UNIT, $idUnitDel);
				if ($delUnit->deleteMatosUnit() <= 0) {
					$retour['error'] = "Impossible de supprimer le matériel identifié unitairement...";
				}
			}
		}
		if (! isset($retour['error'])) {
			$delMatos = new Matos('id', $id);
			if ($delMatos->deleteMatos() > 0) {
				$retour['error'] = 'OK';
				$retour['type'] = 'reloadPage';
			}
			else
				$retour['error'] = "Impossible de supprimer le matériel...";
		}
	}
	catch (Exception $e){
		$retour['error'] = "Impossible de supprimer le matériel... <br />" . $e->getMessage() ; 
	}
	echo json_encode($retour);
}


// modification de l'ordre des sous catégories
if ( @$action == 'newSsCatOrder' ) {
	if (!is_array($ssCat)) die('La liste des sous catégories est manquante, ou ce n\'est pas un tableau...');
	$scmu = new Infos(TABLE_MATOS_CATEG);
	try {
		foreach($ssCat as $newOrder => $idSsCat) {
			$newOrder ++;
			$scmu->loadInfos('id', $idSsCat);
			$scmu->addInfo('ordre', $newOrder);
			$scmu->save();
		}
		$retour['error'] = 'OK';
		$retour['type'] = 'reloadPage';
	}
	catch (Exception $e) {
		$retour['error'] = "Impossible de mettre à jour la liste des sous catégories... Message d'erreur :\n\n". $e->getMessage();
	}
	$retour = json_encode($retour);
	echo $retour;
}


// ajout d'une sous catégorie
if ( @$action == 'addSsCat') {
	try {
		$scm = new Infos(TABLE_MATOS_CATEG);
		$scm->addInfo('label', $label);
		$scm->addInfo('ordre', $ordre);
		$scm->save();
		$retour['error'] = 'OK';
		$retour['type'] = 'reloadModal';
	}
	catch (Exception $e) {
		$retour['error'] = "Impossible de sauvegarder la sous catégorie... Message d'erreur :\n\n". $e->getMessage();
	}
	$retour = json_encode($retour);
	echo $retour;
}


// modification d'un nom de sous catégorie de matériel
if ( @$action == "modifSsCat") {
	try {
		$scm = new Infos(TABLE_MATOS_CATEG);
		$scm->loadInfos('id', $id);
		$scm->addInfo('label', $newLabel);
		$scm->save();
		$retour['error'] = 'OK';
	}
	catch (Exception $e) {
		$retour['error'] = "Impossible de sauvegarder la sous catégorie... Message d'erreur :\n\n". $e->getMessage();
	}
	$retour = json_encode($retour);
	echo $retour;
}

if ( @$action == 'supprSsCat') {
	try {
		$scm = new Infos(TABLE_MATOS_CATEG);
		$scm->delete('id', $id);
		$retour['error'] = 'OK';
		$retour['type'] = 'reload';
	}
	catch (Exception $e) {
		$retour['error'] = "Impossible de supprimer la sous catégorie... Message d'erreur :\n\n". $e->getMessage();
	}
	$retour = json_encode($retour);
	echo $retour;
}


?>
