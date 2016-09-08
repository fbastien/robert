<?php

session_start();
require_once ('initInclude.php');
require_once ('common.inc.php');		// OBLIGATOIRE pour les sessions, à placer TOUJOURS EN HAUT du code !!
require_once ('checkConnect.php' );

	if ( ! isset($_GET['dir']) || ! isset ( $_GET['file']) ) die('Il me manque le dossier et/ou le fichier à télécharger !') ; 

	extract ($_GET) ;

	$file = urldecode($file);
	
	switch( $dir ) {
		case 'sql':
			$dir = FOLDER_DUMP_SQL;
			$mime = 'text/plain';
			break;
		case 'PlanDevis':
			if ($planID == null || $planID == '')
				die("J'ai besoin de l'ID du plan pour vous envoyer le devis !") ;
			$dir = FOLDER_PLANS_DATA . $planID . '/devis' ;
			$mime = 'application/pdf';
			break;
		case 'PlanFacture':
			if ($planID == null || $planID == '')
				die("J'ai besoin de l'ID du plan pour vous envoyer la facture !") ;
			$dir = FOLDER_PLANS_DATA . $planID . '/facture' ;
			$mime = 'application/pdf';
			break;
		case 'PlanFichier':
			if ($planID == null || $planID == '')
				die("J'ai besoin de l'ID du plan pour vous envoyer le fichier !") ;
			$dir = FOLDER_PLANS_DATA . $planID ;
			$ext = strtolower (substr( $file, strrpos( $file, '.')) );
			if ( $ext == 'jpg' || $ext == 'jpeg' || $ext == 'bmp')
				$mime = 'image/jpeg';
			elseif ( $ext == 'pdf')
				$mime = 'application/pdf';
			break;
		case 'Tekos':
			if ($idTekos == null || $idTekos == '')
				die("J'ai besoin de l'ID du technicien pour vous envoyer le fichier !") ;
			$dir = FOLDER_TEKOS_DATA . strtolower($idTekos) ;
			$ext = strtolower (substr( $file, strrpos( $file, '.')) );
			if ( $ext == 'jpg' || $ext == 'jpeg' || $ext == 'bmp')
				$mime = 'image/jpeg';
			elseif ( $ext == 'pdf')
				$mime = 'application/pdf';
			break;
		case 'doc':
			if( $file == '' )
				die("J'ai besoin du nom du fichier à ouvrir !") ;
			$dir = rtrim(FOLDER_CONFIG, '/');
			if( $file != 'exemple_inventaire.xls' )
				die("Fichier inexistant !");
			$ext = strtolower(substr($file, strrpos($file, '.')));
			if( $ext == 'xls' )
				$mime = 'application/vnd.ms-excel';
			break;
		default:
			die('Dossier non accessible !');
	}

	$filename = "../$dir/$file" ;
	
	if ( !file_exists( $filename ) ){
		die( $filename . ' : Fichier inexistant !');
	}
	
	$size = filesize($filename);
	$newFileName = preg_replace('/ /', '_', $file);
		
	header("Content-type: " . $mime );
	header("Content-Disposition: attachment; filename=$newFileName");
	header("Content-Length: $size");
	header("Pragma: no-cache");
	header("Expires: 0");
	
	ob_clean();
	flush(); 
	readfile($filename);

?>
