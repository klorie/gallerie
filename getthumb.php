<?php
require_once "config.php";
require_once "common.php";

$file = "";
$dir  = "";
if ((isset($_REQUEST['file'])) and (isset($_REQUEST['dir']))) {
	$file = $_REQUEST['file'];
	$dir  = $_REQUEST['dir'];	
} else {
	header('HTTP/1.1 400 Bad Request');
	die('file and/or dir was not specified');
}

global $image_folder;
global $thumb_folder;
global $thumb_create;

$info = pathinfo("$image_folder/$dir/$file");
$fname_noext = $info['filename'];

$thumbnail = $thumb_folder.'/'.$dir.'/'.$fname_noext.'.'.$thumb_create;

if (!file_exists($thumbnail)) 
	createSingleThumb($dir, $file);

$gmdate_mod = gmdate("D, d M Y H:i:s", filemtime($thumbnail));
if(! strstr($gmdate_mod, "GMT")) {
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

$fileSize = filesize ($thumbnail);

// send headers then display image
header ('Content-Type: image/' . $thumb_create);
header ('Accept-Ranges: bytes');
header ('Last-Modified: ' . $gmdate_mod);
header ('Content-Length: ' . $fileSize);
header ('Cache-Control: max-age=9999, must-revalidate');
header ('Expires: ' . $gmdate_mod);

readfile ($thumbnail);
die();

?>
