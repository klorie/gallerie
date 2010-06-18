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

global $enable_otf_gen;
global $image_folder;
global $resize_folder;

$info = pathinfo("$image_folder/$dir/$file");
$fname_noext = $info['filename'];
// Fix for php < 5.2
if ($fname_noext == "" ) {
    $fname_noext = substr($info['basename'], 0, strlen($info['basename'])-4);
}

$resize = $resize_folder.'/'.$dir.'/'.$fname_noext.'.jpg';

if (!file_exists($resize))
    if ($enable_otf_gen == 1)
        createResize($dir, $file);
    else
        $resize = './images/nothumb.jpg';
else if (filemtime($resize) < filemtime("$image_folder/$dir/$file"))
    if ($enable_otf_gen == 1)
        createResize($dir, $file);

$gmdate_mod = gmdate("D, d M Y H:i:s", filemtime($resize));
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

$fileSize = filesize($resize);

// send headers then display image
header ('Content-Type: image/jpeg');
header ('Accept-Ranges: bytes');
header ('Last-Modified: ' . $gmdate_mod);
header ('Content-Length: ' . $fileSize);
header ('Cache-Control: max-age=3600, must-revalidate');
header ('Expires: ' . $gmdate_mod);

readfile ($resize);
die();

?>
