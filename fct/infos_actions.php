<?php
session_start();
require_once ('initInclude.php');
require_once ('common.inc.php');		// OBLIGATOIRE pour les sessions, à placer TOUJOURS EN HAUT du code !!
require_once ('checkConnect.php' );

if ( $_SESSION["user"]->isAdmin() !== true ) { die("Vous n'avez pas accès à cette partie du Robert."); }

extract($_POST) ;
$confFile = $install_path . FOLDER_CONFIG . 'config.ini';

if ($action == 'modifConsts') {
	unset($_POST['action']);
	$newIniFile = "";
	foreach ($config as $key => $val) {
		$newIniFile .= "$key = ";
		$htmlKey = preg_replace('/\./', '_', $key);
		$newVal = $val;
		if(isset($_POST[$htmlKey])) {
			$newVal = $_POST[$htmlKey];
			if($key === 'boite.TVA.val') {
				$newVal = (float) $newVal;
			}
		}
		if(is_string($newVal)) {
			if($newVal !== "") {
				$newIniFile .= '"'.preg_replace('/"/', '\"', $newVal).'"';
			}
		}
		else {
			$newIniFile .= $newVal;
		}
		$newIniFile .= "\n";
	}
	
	if ( file_put_contents($confFile, $newIniFile, LOCK_EX) !== false )
		echo 'Informations sauvegardées.';
	else echo 'Impossible de sauvegarder les infos...';
}










?>