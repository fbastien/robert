<?php
function getDumpList () {
	$list = array();
	$dir = opendir($install_path.FOLDER_DUMP_SQL);
	while (($fileSQL = readdir($dir)) !== false) {
		if ($fileSQL != '.' && $fileSQL != '..' && $fileSQL != '.gitignore')
		$list[] = $fileSQL;
	}
	rsort($list, SORT_STRING);
	return $list;
}

function getTableList () {
	global $bdd;
	$q = $bdd->prepare('SHOW TABLES');
	$q->execute();
	$tablesNames = $q->fetchAll(PDO::FETCH_COLUMN);
	return $tablesNames ;
}

function getImportList () {
	$list = array();
	$dir = opendir($install_path.FOLDER_IMPORT_MATOS);
	while (($file = readdir($dir)) !== false) {
		if ($file != '.' && $file != '..' && $file != '.gitignore')
		$list[] = $file;
	}
	rsort($list, SORT_STRING);
	return $list;
}

?>
