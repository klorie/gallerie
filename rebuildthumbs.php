<?php

// Start PHP code
require "common.php";
require "config.php";

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

// Clean all unneeded thumbnails
deleteDir( "./thumbnails" ) ;
mkdir("./thumbnails");

//$dirlist = getFileList("", true, 10, "./gallery");
$dirlist = getFileList("");
echo count($dirlist[dir]);

foreach($dirlist[dir] as $file) {
   $path = $file['fullname'];
   echo "$path";
   if (!file_exists("./thumbnails/$path")) { mkdir("./thumbnails/$path"); }
   if ($thumb_creation == "imagick") {
     createThumbsImagick( "./gallery/$path", "./thumbnails/$path", $thumb_size ) ;
     $thumb_file_ext = "png";
   } else {
     createThumbs( "./gallery/$path", "./thumbnails/$path", $thumb_size ) ;
     $thumb_file_ext = "jpg";
   }
}
$mtime = explode(' ', microtime());
$totaltime = $mtime[0] + $mtime[1] - $starttime;
printf('Page loaded in %.3f seconds.', $totaltime);

?>
