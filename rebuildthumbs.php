<?php

// Start PHP code
require "common.php";
require "config.php";

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

if ($argv[1] == "--clean") {
  // Clean all unneeded thumbnails
  echo "Removing all thumbnails\n";
  deleteDir( "$thumb_folder" ) ;
  mkdir("$thumb_folder");
}

$dirlist = getFileList("", true, 10);
echo "Found ".count($dirlist[dir])." to process\n";

foreach($dirlist[dir] as $file) {
   $path = $file['fullname'];
   echo "Processing $path ...";
   if (!file_exists("$thumb_folder/$path")) { mkdir("$thumb_folder/$path", 0777, true); }
	createThumbs("$path") ;
   echo " [ Done ]\n";
}
# Remove cached pages
deleteDir("./cache/");
mkdir("./cache");

$mtime = explode(' ', microtime());
$totaltime = $mtime[0] + $mtime[1] - $starttime;
echo "Processing done in $totaltime seconds\n";

?>
