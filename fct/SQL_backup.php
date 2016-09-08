<?php
session_start();
require_once ('initInclude.php');
require_once ('common.inc.php');		// OBLIGATOIRE pour les sessions, à placer TOUJOURS EN HAUT du code !!
require_once ('checkConnect.php' );

if ( $_SESSION["user"]->isAdmin() !== true ) { die("Vous n'avez pas accès à cette partie du Robert."); }

$dumpPath = $install_path . FOLDER_DUMP_SQL;
if (!is_dir($dumpPath))
	mkdir($dumpPath);
$importPath = $install_path . FOLDER_IMPORT_MATOS;
if (!is_dir($importPath))
	mkdir($importPath);
$codeAuthentik = md5('systemFlaskSQLbackup');
global $dumpPath;		// Chemin vers les backup SQL
global $codeAuthentik;	// Code "d'authenticité" pour la création / récupération


/**
 * Fonction de dump SQL dans un fichier.
 * 
 * @param string|array $toBakup 'all', ou array() des tables, ou string des tables sép. par des ','
 */
function backupSQL ($toBakup='all') { 
	global $dumpPath;
	global $bdd;
	global $codeAuthentik;
	$now = date('Y-m-d');
	$fileSQL = array();
	
	if ($toBakup == 'all') {													// Si on dump TOUTES les tables
		$q = $bdd->prepare('SHOW TABLES');
		$q->execute();
		$tables = $q->fetchAll(PDO::FETCH_COLUMN);
		$fileSQL = 'TOUT_'.$now.'.sql';
	}
	else {																		// Si on dump QUE CERTAINES tables
		$tables  = is_array($toBakup) ? $toBakup : explode(',',$toBakup);
		if (count($tables) > 1) {
			$fileSQL = '';
			foreach($tables as $tableName) {
				$fileSQL .= $tableName.'_';
			}
			$fileSQL .= $now.'.sql';
		}
		else $fileSQL = $tables[0].'_'.$now.'.sql';								// Si on dump QU'UNE SEULE table
	}
	
	$output  = "\n-- BACKUP BASE DE DONNÉES -- \n";								// Création du texte du fichier SQL ( -> $output )
	$output .= "-- DATE (AA-MM-JJ): $now \n";
	$output .= "-- FAITE PAR : ".$_SESSION['user']->getUserInfos('prenom')."\n";
	$output .= "-- $codeAuthentik \n\n";
	$output .= 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";';
	$output .= "\n\n";
	foreach($tables as $table) {
		$c = $bdd->prepare('SHOW CREATE TABLE `'.$table.'`');			// Récup les types de champs de la table
		try { $c->execute(); }
		catch (Exception $e) { echo "erreur SQL : $e"; return false; }	// Si la table n'existe pas, erreur et on arrête tout !
		$resultCreate = $c->fetchAll(PDO::FETCH_ASSOC);
		
		$r = $bdd->prepare("SELECT * FROM $table");						// Récup les valeurs des champs de la table
		$r->execute();
		$resultTable = $r->fetchAll(PDO::FETCH_ASSOC);
		$nbRec = count($resultTable);									// Compte le nombre d'enregistrements de la table
		
		$output .= "-- ----------------- TABLE $table ------------------------\n\n";
		$output .= "DROP TABLE IF EXISTS `$table`;\n\n";				// $output : commande de suppression de la table si déjà existante
		$output .= $resultCreate[0]['Create Table'].";\n\n";			// $output : commande de re-création de la table
		
		if ($nbRec != 0) {
			$output .= "INSERT INTO `$table` VALUES ";						// $output : commande d'insertion des valeurs dans la table
			$countRec = 0;
			foreach ($resultTable as $row) {
				$countRec++ ;
				$output .= "\n(";
				$nbVal = count($row);										// Compte le nombre de colonnes de la table
				$countVal = 0;
				foreach ($row as $value) {
					$countVal++ ;
					$value = addslashes($value);							// valeur : ajoute des slashes devant les caractères réservés
					$value = preg_replace("/\\r\\n/", "/\\\r\\\n/", $value);// valeur : évite que les retours à la ligne soient traduits
					if (isset($value)) $output .= "'$value'" ;				// $output : valeur à ajouter ('' si pas de valeur)
					else $output .= "''";
					if ($countVal == $nbVal) {
						$output .= ")";										// $output : ajout de la parenthèse fermée si à la fin des colonnes
						if ($countRec == $nbRec) $output .= ";";			// $output : ajout du point virgule si à la fin des enregistrements
						else $output .= ",";								// $output : ajout de la virgule si pas encore à la fin des enregistrements
					}
					else $output .= ",";									// $output : ajout de la virgule si pas encore à la fin des colonnes
				}
			}
		}
		$output .= "\n\n\n";
	}
																		// Sauvegarde de(s) fichier(s) SQL
	if (file_put_contents($dumpPath.$fileSQL, (string)$output) !== false)
		return $fileSQL;
	else return false;
}


/**
 * Fonction de récupération de fichier SQL.
 */
function restoreSQL ($sqlFile) {
	global $dumpPath;
	global $bdd;
	global $codeAuthentik;
	if (file_exists($dumpPath.$sqlFile)) {								// Si le fichier existe
		$SQLcontent = file_get_contents($dumpPath.$sqlFile);
		if (preg_match("/-- $codeAuthentik/", $SQLcontent)) {			// Si le fichier contiens bien le hash MD5 créé lors d'une sauvegarde via le "Dump"
			$q = $bdd->prepare($SQLcontent);							// Execution de la requête du fichier
			try {
				$q->execute();
				$retour = $sqlFile;
			} catch (Exception $e) {
				$retour = "erreur SQL : $e";
			}
		}
		else {
			echo 'CODE de sécurité ERRONÉ ! ';
			$retour = false;
		}
	}
	else {
		echo 'FICHIER INTROUVABLE ! ';
		$retour = false ;
	}
	return $retour;
}


/**
 * Fonction d'import d'inventaire à partir d'un fichier Excel.
 */
function importInventaire($file) {
	global $importPath;
	global $bdd;
	define('CSV_FIELD_COUNT', 13);
	define('CSV_ID', 0);
	define('CSV_CODE', 1);
	define('CSV_REF', 2);
	define('CSV_LABEL', 3);
	define('CSV_CATEG', 4);
	define('CSV_SOUS_CATEG', 5);
	define('CSV_QUANTITE', 6);
	define('CSV_PANNE', 7);
	define('CSV_TARIF', 8);
	define('CSV_VAL_REMP', 9);
	define('CSV_REMARQUE', 10);
	define('CSV_DATE', 11);
	define('CSV_EXT', 12);
	define('CSV_LINE_NEW', '*');
	define('CSV_LINE_UNIT', '+');
	
	$filePath = $importPath.$file;
	if (file_exists($filePath)) {
		if (($handle = fopen($filePath, "r")) !== false) {
			// Préparation des différentes requêtes SQL
			$selectSousCategStmt = $bdd->prepare("SELECT `id` FROM `robert_matos_sous_cat` WHERE `label` = :label");
			$insertSousCategStmt = $bdd->prepare("INSERT INTO `robert_matos_sous_cat` (`label`, `ordre`) VALUES (:label, (SELECT MAX(`ordre`) + 1 FROM `robert_matos_sous_cat` OLD))");
			$insertMatosStmt = $bdd->prepare("INSERT INTO `robert_matos_detail` (`label`, `ref`, `categorie`, `sousCateg`, `tarifLoc`, `valRemp`, `remarque`) VALUES (:label, :ref, :categorie, :sousCateg, :tarifLoc, :valRemp, :remarque)");
			$insertMatosGenStmt = $bdd->prepare("INSERT INTO `robert_matos_generique` (`id_matosdetail`, `quantite`, `panne`, `dateAchat`, `ownerExt`) VALUES (:id, :quantite, :panne, :dateAchat, :ownerExt)");
			$insertMatosUnitStmt = $bdd->prepare("INSERT INTO `robert_matos_ident` (`id_matosdetail`, `ref`, `panne`, `dateAchat`, `ownerExt`, `remarque`) VALUES (:id, :ref, :panne, :dateAchat, :ownerExt, :remarque)");
			$selectDetailCountStmt = $bdd->prepare("SELECT COUNT(*) FROM `robert_matos_detail` WHERE `id` = :id");
			$selectIdRefStmt = $bdd->prepare("SELECT `id` FROM `robert_matos_detail` WHERE `ref` = :reference AND `id` <> :id UNION SELECT `id_matosident` AS `id` FROM `robert_matos_ident` WHERE `ref` = :reference AND `id_matosident` <> :id");
			// TODO check code-barres
			$selectGenCountStmt = $bdd->prepare("SELECT COUNT(*) FROM `robert_matos_generique` WHERE `id_matosdetail` = :id");
			$selectUnitCountStmt = $bdd->prepare("SELECT COUNT(*) AS `quantite`, SUM(`panne`) AS `panne` FROM `robert_matos_ident` WHERE `id_matosdetail` = :id");
			$updateMatosStmt = $bdd->prepare("UPDATE `robert_matos_detail` SET `label` = :label, `ref` = :ref, `categorie` = :categorie, `sousCateg` = :sousCateg, `tarifLoc` = :tarifLoc, `valRemp` = :valRemp, `remarque` = :remarque WHERE `id` = :id");
			$updateMatosGenStmt = $bdd->prepare("UPDATE `robert_matos_generique` SET `quantite` = :quantite, `panne` = :panne, `dateAchat` = :dateAchat, `ownerExt` = :ownerExt WHERE `id_matosdetail` = :id");
			$deleteMatosGenStmt = $bdd->prepare("DELETE FROM `robert_matos_generique` WHERE `id_matosdetail` = :id");
			$updateQuantiteGenStmt = $bdd->prepare("UPDATE `robert_matos_generique` SET `quantite` = `quantite` - 1, `panne` = `panne` - :panne WHERE `id_matosdetail` = :id");
			
			try {
				$rowNumber = 0;
				$reset = true;
				while (($data = fgetcsv($handle, 0, ';', '"', '"')) !== false) {
					$rowNumber++;
					if($reset) {
						$reset = false;
						$idMatos = -1;
						$matosNonUnitCount = 0;
						$matosPanneCount = 0;
					}
					// Ligne invalide ou de commentaire
					if(count($data) != CSV_FIELD_COUNT || ! preg_match('/^([0-9]+|['.CSV_LINE_NEW.CSV_LINE_UNIT.'])$/', $data[CSV_ID])) {
						$reset = true;
						continue;
					}
					
					// Vérification des informations de la ligne (communes à tous les cas)
					if(empty($data[CSV_REF]) && empty($data[CSV_CODE])) {
						echo "WARNING ligne $rowNumber ignorée : code-barres et/ou référence manquants.<br />";
						$reset = true;
						continue;
					} else {
						// TODO remove et gérer les codes-barres
						if(empty($data[CSV_REF])) {
							$data[CSV_REF] = $data[CSV_CODE];
						}
						
						// Vérification que la référence et le code-barres ne sont pas déjà utilisés
						$selectIdRefStmt->bindValue(':reference', $data[CSV_REF]);
						$selectIdRefStmt->bindValue(':id', ($data[CSV_ID] === CSV_LINE_NEW || $data[CSV_ID] === CSV_LINE_UNIT ? -1 : $data[CSV_ID]), PDO::PARAM_INT);
						if(! $selectIdRefStmt->execute()) {
							echo "ERREUR ligne $rowNumber : ".$selectIdRefStmt->errorInfo()[2];
							$reset = true;
							continue;
						}
						if($selectIdRefStmt->fetchColumn() > 0) {
							echo "WARNING ligne $rowNumber ignorée : référence déjà utilisée.<br />";
							$reset = true;
							continue;
						}
						// TODO check code-barres
					}
					if(filter_var($data[CSV_PANNE], FILTER_VALIDATE_INT, array('options' => array('min_range' => 0))) === false) {
						if(empty($data[CSV_PANNE])) {
							$data[CSV_PANNE] = 0;
						} else {
							echo "WARNING ligne $rowNumber ignorée : quantité en panne invalide.<br />";
							$reset = true;
							continue;
						}
					}
					if(empty($data[CSV_REMARQUE])) {
						$data[CSV_REMARQUE] = null;
					}
					if(! empty($data[CSV_DATE]) && ! empty($data[CSV_EXT])) {
						echo "WARNING ligne $rowNumber ignorée : date d'achat et prestataire externe renseignés en même temps.<br />";
						$reset = true;
						continue;
					}
					if(empty($data[CSV_DATE])) {
						$data[CSV_DATE] = (empty($data[CSV_EXT]) ? '0000-00-00' : null);
					} else {
						$date = DateTime::createFromFormat('Y-m-d', $data[CSV_DATE]);
						if(! ($date && $date->format('Y-m-d') == $data[CSV_DATE])) {
							echo "WARNING ligne $rowNumber ignorée : date d'achat invalide.<br />";
							$reset = true;
							continue;
						}
					}
					if(empty($data[CSV_EXT])) {
						$data[CSV_EXT] = null;
					}
					
					// Continuation d'une ligne précédente pour du matériel identifié individuellement
					if($data[CSV_ID] === CSV_LINE_UNIT) {
						if($idMatos < 0) {
							echo "WARNING ligne $rowNumber ignorée : ID = \"".CSV_LINE_UNIT."\" mais la ligne précédente est invalide.<br />";
							$reset = true;
							continue;
						}
						// Vérification que le nombre de matériel identifié ne dépasse pas la quantité totale renseignée
						if($matosNonUnitCount <= 0) {
							echo "WARNING ligne $rowNumber ignorée : nombre de matériel identifié supérieur à la quantité totale.<br />";
							$reset = true;
							continue;
						}
						
						// Vérification des informations de la ligne (spécifiques aux lignes de matériel unitaire)
						if($data[CSV_PANNE] > 1) {
							echo "WARNING ligne $rowNumber ignorée : quantité en panne invalide.<br />";
							continue;
						}
						// Vérification que le nombre de matériel identifié en panne ne dépasse pas la quantité totale en panne renseignée
						if($data[CSV_PANNE] > $matosPanneCount) {
							echo "WARNING ligne $rowNumber ignorée : nombre de matériel identifié en panne supérieur à la quantité en panne totale.<br />";
							continue;
						}
						// Vérification que, sans compter le matériel unitaire, la quantité en panne ne devienne pas supérieure à la quantité restante
						// Autrement dit, il ne faut pas que la ligne ne soit pas en panne alors que tout le matériel non identifié restant est en panne
						if($matosPanneCount - $data[CSV_PANNE] >= $matosNonUnitCount) {
							echo "WARNING ligne $rowNumber ignorée : quantité en panne invalide.<br />";
							$reset = true;
							continue;
						}
						
						// Insertion dans la table robert_matos_ident
						$insertMatosUnitStmt->bindValue(':id', $idMatos, PDO::PARAM_INT);
						$insertMatosUnitStmt->bindValue(':ref', $data[CSV_REF]);
						$insertMatosUnitStmt->bindValue(':panne', $data[CSV_PANNE], PDO::PARAM_INT);
						$insertMatosUnitStmt->bindValue(':dateAchat', $data[CSV_DATE]);
						$insertMatosUnitStmt->bindValue(':ownerExt', $data[CSV_EXT]);
						$insertMatosUnitStmt->bindValue(':remarque', $data[CSV_REMARQUE]);
						if(! $insertMatosUnitStmt->execute()) {
							echo "ERREUR ligne $rowNumber : ".$insertMatosStmt->errorInfo()[2];
							continue;
						}
						
						// Mise à jour de la table robert_matos_generique
						$matosNonUnitCount--;
						$matosPanneCount -= $data[CSV_PANNE];
						// Si tout le matériel est identifié individuellement, il ne doit plus y avoir d'enregistrement dans le matériel générique
						if($matosNonUnitCount == 0) {
							$deleteMatosGenStmt->bindValue(':id', $idMatos, PDO::PARAM_INT);
							if(! $deleteMatosGenStmt->execute()) {
								echo "ERREUR ligne $rowNumber : ".$deleteMatosGenStmt->errorInfo()[2];
								$reset = true;
								continue;
							}
							// Sinon mise à jour de la quantité dans robert_matos_generique qui n'inclut pas le matériel identifié
						} else {
							$updateQuantiteGenStmt->bindValue(':id', $idMatos, PDO::PARAM_INT);
							$updateQuantiteGenStmt->bindValue(':panne', $data[CSV_PANNE], PDO::PARAM_INT);
							if(! $updateQuantiteGenStmt->execute()) {
								echo "ERREUR ligne $rowNumber : ".$updateQuantiteGenStmt->errorInfo()[2];
								$reset = true;
								continue;
							}
						}
					}
					// Cas normal d'une ligne pour un matériel groupé
					else {
						// Vérification des informations de la ligne (spécifiques aux lignes de matériel groupé)
						if(empty($data[CSV_LABEL])) {
							echo "WARNING ligne $rowNumber ignorée : désignation manquante.<br />";
							$reset = true;
							continue;
						}
						if(! in_array($data[CSV_CATEG], array('son', 'lumiere', 'structure', 'transport', 'divers'))) { //TODO gérer catégorie divers
							echo "WARNING ligne $rowNumber ignorée : catégorie invalide.<br />";
							$reset = true;
							continue;
						}
						if(filter_var($data[CSV_QUANTITE], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) === false) {
							echo "WARNING ligne $rowNumber ignorée : quantité invalide.<br />";
							$reset = true;
							continue;
						}
						if($data[CSV_PANNE] > $data[CSV_QUANTITE]) {
							echo "WARNING ligne $rowNumber ignorée : quantité en panne invalide.<br />";
							$reset = true;
							continue;
						}
						if(filter_var($data[CSV_TARIF], FILTER_VALIDATE_FLOAT) === false || (float) $data[CSV_TARIF] < 0) {
							if(empty($data[CSV_TARIF])) {
								$data[CSV_TARIF] = 0;
							} else {
								echo "WARNING ligne $rowNumber ignorée : tarif de location invalide.<br />";
								$reset = true;
								continue;
							}
						}
						if(filter_var($data[CSV_VAL_REMP], FILTER_VALIDATE_FLOAT) === false || (float) $data[CSV_VAL_REMP] < 0) {
							if(empty($data[CSV_VAL_REMP])) {
								$data[CSV_VAL_REMP] = 0;
							} else {
								echo "WARNING ligne $rowNumber ignorée : valeur de remplacement invalide.<br />";
								$reset = true;
								continue;
							}
						}
						
						// Récupération de l'ID de la sous-catégorie
						if(empty($data[CSV_SOUS_CATEG])) {
							$idSousCateg = 0;
						} else {
							$selectSousCategStmt->bindValue(':label', $data[CSV_SOUS_CATEG]);
							if(! $selectSousCategStmt->execute()) {
								echo "ERREUR ligne $rowNumber : ".$selectSousCategStmt->errorInfo()[2];
								$reset = true;
								continue;
							}
							if($selectSousCategStmt->rowCount() > 0) {
								$idSousCateg = $selectSousCategStmt->fetchColumn();
							}
							// Création de la sous-catégorie si elle n'existe pas
							else {
								$insertSousCategStmt->bindValue(':label', $data[CSV_SOUS_CATEG]);
								if(! $insertSousCategStmt->execute()) {
									echo "ERREUR ligne $rowNumber : ".$insertSousCategStmt->errorInfo()[2];
									$reset = true;
									continue;
								}
								$idSousCateg = $bdd->lastInsertId();
							}
						}
						
						// Ajout de nouveau matériel
						if($data[CSV_ID] === CSV_LINE_NEW) {
							// Insertion dans la table robert_matos_detail
							$insertMatosStmt->bindValue(':label', $data[CSV_LABEL]);
							$insertMatosStmt->bindValue(':ref', $data[CSV_REF]);
							$insertMatosStmt->bindValue(':categorie', $data[CSV_CATEG]);
							$insertMatosStmt->bindValue(':sousCateg', $idSousCateg, PDO::PARAM_INT);
							$insertMatosStmt->bindValue(':tarifLoc', $data[CSV_TARIF]);
							$insertMatosStmt->bindValue(':valRemp', $data[CSV_VAL_REMP]);
							$insertMatosStmt->bindValue(':remarque', $data[CSV_REMARQUE]);
							if(! $insertMatosStmt->execute()) {
								echo "ERREUR ligne $rowNumber : ".$insertMatosStmt->errorInfo()[2];
								$reset = true;
								continue;
							}
							$idMatos = $bdd->lastInsertId();
								
							// Insertion dans la table robert_matos_generique
							$insertMatosGenStmt->bindValue(':id', $idMatos, PDO::PARAM_INT);
							$insertMatosGenStmt->bindValue(':quantite', $data[CSV_QUANTITE], PDO::PARAM_INT);
							$insertMatosGenStmt->bindValue(':panne', $data[CSV_PANNE], PDO::PARAM_INT);
							$insertMatosGenStmt->bindValue(':dateAchat', $data[CSV_DATE]);
							$insertMatosGenStmt->bindValue(':ownerExt', $data[CSV_EXT]);
							if(! $insertMatosGenStmt->execute()) {
								echo "ERREUR ligne $rowNumber : ".$insertMatosGenStmt->errorInfo()[2];
								$reset = true;
								continue;
							}
							$matosNonUnitCount = $data[CSV_QUANTITE];
							$matosPanneCount = $data[CSV_PANNE];
						}
						// Modification de matériel existant
						else {
							$idMatos = $data[CSV_ID];
								
							// Vérification que l'id correspond à un matériel existant
							$selectDetailCountStmt->bindValue(':id', $idMatos, PDO::PARAM_INT);
							if(! $selectDetailCountStmt->execute()) {
								echo "ERREUR ligne $rowNumber : ".$selectDetailCountStmt->errorInfo()[2];
								$reset = true;
								continue;
							}
							if($selectDetailCountStmt->fetchColumn() == 0) {
								echo "WARNING ligne $rowNumber ignorée : id ne correspondant à aucun matériel existant.<br />";
								$reset = true;
								continue;
							}
								
							// Récupération de la répartition actuelle de matériel entre le générique et celui identifié individuellement
							// Table robert_matos_ident
							$selectUnitCountStmt->bindValue(':id', $idMatos, PDO::PARAM_INT);
							if(! $selectUnitCountStmt->execute()) {
								echo "ERREUR ligne $rowNumber : ".$selectUnitCountStmt->errorInfo()[2];
								$reset = true;
								continue;
							}
							$selectUnitCountStmt->bindColumn('quantite', $matosUnitCount);
							$selectUnitCountStmt->bindColumn('panne', $matosUnitPanneCount);
							$selectUnitCountStmt->fetch(PDO::FETCH_BOUND);
							// Table robert_matos_generique
							$selectGenCountStmt->bindValue(':id', $idMatos, PDO::PARAM_INT);
							if(! $selectGenCountStmt->execute()) {
								echo "ERREUR ligne $rowNumber : ".$selectGenCountStmt->errorInfo()[2];
								$reset = true;
								continue;
							}
							$matosGenExists = ($selectGenCountStmt->fetchColumn() > 0);
								
							// Vérification que la quantité et le nombre en panne ne sont pas inférieurs au matériel unitaire existant
							if($data[CSV_QUANTITE] < $matosUnitCount) {
								echo "WARNING ligne $rowNumber ignorée : quantité inférieure au nombre de matériel identifié individuellement déjà présent en base.<br />";
								$reset = true;
								continue;
							}
							if($data[CSV_PANNE] < $matosUnitPanneCount) {
								echo "WARNING ligne $rowNumber ignorée : quantité en panne inférieure au nombre de matériel identifié individuellement en panne déjà présent en base.<br />";
								$reset = true;
								continue;
							}
							$matosNonUnitCount = $data[CSV_QUANTITE] - $matosUnitCount;
							$matosPanneCount = $data[CSV_PANNE] - $matosUnitPanneCount;
							// Vérification que, sans compter le matériel unitaire, la quantité en panne n'est pas supérieure à la quantité restante
							if($matosPanneCount > $matosNonUnitCount) {
								echo "WARNING ligne $rowNumber ignorée : quantité en panne invalide.<br />";
								$reset = true;
								continue;
							}
								
							// Mise à jour de la table robert_matos_detail
							$updateMatosStmt->bindValue(':id', $idMatos, PDO::PARAM_INT);
							$updateMatosStmt->bindValue(':label', $data[CSV_LABEL]);
							$updateMatosStmt->bindValue(':ref', $data[CSV_REF]);
							$updateMatosStmt->bindValue(':categorie', $data[CSV_CATEG]);
							$updateMatosStmt->bindValue(':sousCateg', $idSousCateg, PDO::PARAM_INT);
							$updateMatosStmt->bindValue(':tarifLoc', $data[CSV_TARIF]);
							$updateMatosStmt->bindValue(':valRemp', $data[CSV_VAL_REMP]);
							$updateMatosStmt->bindValue(':remarque', $data[CSV_REMARQUE]);
							if(! $updateMatosStmt->execute()) {
								echo "ERREUR ligne $rowNumber : ".$updateMatosStmt->errorInfo()[2];
								$reset = true;
								continue;
							}
								
							// Mise à jour de la table robert_matos_generique
							if($matosNonUnitCount == 0) {
								// Si tout le matériel est identifié individuellement, il ne doit pas y avoir d'enregistrement dans le matériel générique
								if($matosGenExists) {
									$deleteMatosGenStmt->bindValue(':id', $idMatos, PDO::PARAM_INT);
									if(! $deleteMatosGenStmt->execute()) {
										echo "ERREUR ligne $rowNumber : ".$deleteMatosGenStmt->errorInfo()[2];
										$reset = true;
										continue;
									}
								}
							} else {
								// Si tout le matériel n'est pas identifié individuellement alors que c'était le cas avant, insertion, sinon mise à jour
								$statement = ($matosGenExists ? $updateMatosGenStmt : $insertMatosGenStmt);
								$statement->bindValue(':id', $idMatos, PDO::PARAM_INT);
								$statement->bindValue(':quantite', $matosNonUnitCount, PDO::PARAM_INT);
								$statement->bindValue(':panne', $matosPanneCount, PDO::PARAM_INT);
								$statement->bindValue(':dateAchat', $data[CSV_DATE]);
								$statement->bindValue(':ownerExt', $data[CSV_EXT]);
								if(! $statement->execute()) {
									echo "ERREUR ligne $rowNumber : ".$statement->errorInfo()[2];
									$reset = true;
									continue;
								}
							}
						}
					}
				}
				$retour = $file;
			} catch (Exception $ex) {
				echo "ERREUR SQL ligne $rowNumber : ".$ex->getMessage().'<br />';
				$retour = false;
			}
			fclose($handle);
		}
		else {
			echo 'IMPOSSIBLE D\'OUVRIR LE FICHIER ! ';
			$retour = false;
		}
	}
	else {
		echo 'FICHIER INTROUVABLE ! ';
		$retour = false ;
	}
	return $retour;
}


//////////////////////////////////////////////////////////////////////////////// TRAITEMENT DES ACTIONS VIA _POST

if (! isset($_SESSION["user"]) ) { echo "Pas de session"; return -1; }
else {
	if ($_POST) {
		// ACTION DUMP : enregistre la structure et le contenu de la base MySQL dans un fichier SQL
		if (isset($_POST['dump'])) {
			if(($fileSaved = backupSQL($_POST['dump'])) != false)
				echo 'SAUVEGARDE OK de <b>'.$fileSaved.'</b>.';
			else
				echo 'Impossible de sauvegarder la base de données...';
		}
		// ACTION RESTORE : récupère le contenu d'un fichier SQL et éxécute son contenu dans MySQL
		elseif (isset($_POST['restore'])) {
			if (($fileLoaded = restoreSQL($_POST['fileBackup'])) != false)
				echo "</b>RÉCUPÉRATION de <b>$fileLoaded</b> OK !";
			else
				echo "Impossible de récupérer la sauvegarde...";
		}
		// ACTION IMPORT : récupère le contenu d'un fichier CSV d'inventaire et insère le matériel dans la base de données
		elseif (isset($_POST['import'])) {
			if (($fileLoaded = importInventaire($_POST['fileBackup'])) != false)
				echo "</b>IMPORT de <b>$fileLoaded</b> OK !";
			else
				echo 'Impossible d\'importer l\'inventaire...';
		}
		else
			echo 'aucune action sélectionnée...' ;
	}
	else
		echo 'accès interdit...' ;
}

?>
