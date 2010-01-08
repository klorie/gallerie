<?php
require "config.php";
require "common.php";

$lockfile  = "./gallerie_create_thumbs.lock";

if (file_exists($lockfile)) {
  if (filemtime($lockfile) < (time() - 7200)) {
    touch($lockfile);
    if ($thumb_create == "imagick")
      createThumbsImagick($argv[1], $argv[2], $thumb_size);
    else
      createThumbs($argv[1], $argv[2], $thumb_size);  
    unlink($lockfile);
  }
} else {
  touch($lockfile);
  if ($thumb_create == "imagick")
    createThumbsImagick($argv[1], $argv[2], $thumb_size);
  else
    createThumbs($argv[1], $argv[2], $thumb_size);  
  unlink($lockfile);
}

?>
