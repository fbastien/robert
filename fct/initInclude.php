<?php

$install_path = dirname(__DIR__).DIRECTORY_SEPARATOR;

$pathInc  = $install_path."inc";
$pathConf = $install_path."config";
$pathClass = $install_path."classes";
$pathFonts = $install_path."font";
set_include_path(get_include_path() . PATH_SEPARATOR . $pathInc . PATH_SEPARATOR . $pathConf . PATH_SEPARATOR . $pathClass . PATH_SEPARATOR . $pathFonts);

?>
