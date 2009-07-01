<?php
require "common.php";

$lockfile  = "/tmp/gallerie_create_thumbs.lock";
$thumbsize = 150;

if (file_exists($lockfile) {
  if (filemtime($lockfile) > (time() - 7200) {
    touch($lockfile);
    createThumbs($argv[1], $argv[2], $thumbsize);
    unlink($lockfile);
  }
} else {
  touch($lockfile);
  createThumbs($argv[1], $argv[2], $thumbsize);
  unlink($lockfile);
}

?>
