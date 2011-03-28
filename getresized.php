<?php
require_once "resized.php";

$object = -1;
if (isset($_REQUEST['id'])) {
    $object = $_REQUEST['id'];
} else {
	header('HTTP/1.1 400 Bad Request');
	die('id was not specified');
}

global $resized_folder;

$resized = baseDir()."/$resized_folder/".getResizedPath($object);
    
if (!file_exists($resized)) {
    $resized = baseDir().'/images/nothumb.jpg';
}

$gmdate_mod = gmdate("D, d M Y H:i:s", filemtime($resized));
if(!strstr($gmdate_mod, "GMT")) {
	$gmdate_mod .= " GMT";
}
if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
	// check for updates
	$if_modified_since = preg_replace ("/;.*$/", "", $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
	if ($if_modified_since == $gmdate_mod) {
		header("HTTP/1.1 304 Not Modified");
		die();
	}
}

$fileSize = filesize($resized);

// send headers then display image
header ('Content-Type: image/jpeg');
header ('Accept-Ranges: bytes');
header ('Last-Modified: ' . $gmdate_mod);
header ('Content-Length: ' . $fileSize);
header ('Cache-Control: max-age=3600, must-revalidate');
header ('Expires: ' . $gmdate_mod);

readfile ($resized);
die();


?>
