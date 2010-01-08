<?php

// Start PHP code
require "common.php";
require "config.php";

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

// Clean all unneeded thumbnails
deleteDir( "./thumbnails" ) ;
mkdir("./thumbnails");

$dirlist = getFileList("", true, 10);
echo "Found ".count($dirlist[dir])." to process\n";

foreach($dirlist[dir] as $file) {
   $path = $file['fullname'];
   echo "Processing $path ...";
   if (!file_exists("./thumbnails/$path")) { mkdir("./thumbnails/$path", 0777, true); }
   if ($thumb_create == "imagick") {
     createThumbsImagick( "./gallery/$path", "./thumbnails/$path", $thumb_size ) ;
     $thumb_file_ext = "png";
   } else {
     createThumbs( "./gallery/$path", "./thumbnails/$path", $thumb_size ) ;
     $thumb_file_ext = "jpg";
   }
   echo " [ Done ]\n";
}
$mtime = explode(' ', microtime());
$totaltime = $mtime[0] + $mtime[1] - $starttime;
printf('Processing done in %.3f seconds.\n', $totaltime);

?>
