<?php

// Start PHP code
require "common.php";
require "config.php";

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

if ($argv[1] == "--clean") {
  // Clean all unneeded thumbnails
  echo "Removing all thumbnails\n";
  deleteDir( "./thumbnails" ) ;
  mkdir("./thumbnails");
}

$dirlist = getFileList("", true, 10);
echo "Found ".count($dirlist[dir])." to process\n";

foreach($dirlist[dir] as $file) {
   $path = $file['fullname'];
   echo "Processing $path ...";
   if (!file_exists("./thumbnails/$path")) { mkdir("./thumbnails/$path", 0777, true); }
   if ($thumb_create == "jpg") {
     createThumbsJPG( "./gallery/$path", "./thumbnails/$path", $thumb_size ) ;
     $thumb_file_ext = "jpg";
   } else {
     createThumbsPNG( "./gallery/$path", "./thumbnails/$path", $thumb_size ) ;
     $thumb_file_ext = "jpg";
   }
   echo " [ Done ]\n";
}
# Remove cached pages
deleteDir("./cache/");
mkdir("./cache");

$mtime = explode(' ', microtime());
$totaltime = $mtime[0] + $mtime[1] - $starttime;
echo "Processing done in $totaltime seconds\n";

?>
