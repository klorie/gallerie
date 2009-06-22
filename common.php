<?php

function getPathLink($directory) {
  return $_SERVER["PHP_SELF"]."?path=".urlEncode($directory);
}
 
function safeDirectory($path) {
        GLOBAL $displayError;
        $result = $path;
        if (strpos($path,"..")!==false)
                $result = "";
        if (substr($path,0,1)=="/") {
                $result = "";
        }
        if ($result!=$path) {
                $displayError[] = "Illegal path specified, ignoring.";
        }
        return $result;
}

function getFileList($dir, $recurse=false, $depth=false)
{
    # array to hold return value
    $retval = array();

    # open pointer to directory and read list of files
    $d = @dir("./gallery/".$dir) or die("getFileList: Failed opening directory ./gallery/$dir for reading");
    while(false !== ($entry = $d->read())) {
      # skip hidden files
      if($entry[0] == ".") continue;
      if(is_dir("./gallery/$dir/$entry")) {
	# Folder
	if ($dir != "") {
          $retval[] = array(
            "dir"      => "$dir",
            "fullname" => "$dir/$entry",
            "name"     => "$entry",
            "type"     => "dir",
            "title"    => "$entry",
            "size"     => 0,
            "lastmod"  => filemtime("./gallery/$dir/$entry")
          ); } else {
          $retval[] = array(
            "dir"      => "",
            "fullname" => "$entry",
            "name"     => "$entry",
            "type"     => "dir",
            "title"    => "$entry",
            "size"     => 0,
            "lastmod"  => filemtime("./gallery/$entry")
          ); }
        if($recurse && is_readable("./gallery/$dir/$entry")) {
          if($depth === false) {
            $retval = array_merge($retval, getFileList("$dir/$entry", true));
          } elseif($depth > 0) {
            $retval = array_merge($retval, getFileList("$dir/$entry", true, $depth - 1));
          }
        }
      } elseif(is_readable("./gallery/$dir/$entry")) {
	# File
	$info = pathinfo("./gallery/$dir/$entry");
	if (strtolower($info['extension']) != 'jpg') { continue ; }
        $size = getimagesize("./gallery/$dir/$entry", $imginfo);
        $caption = "$entry";
	if (isset($imginfo["APP13"])) {
          $iptc = iptcparse($imginfo["APP13"]);
          if (is_array($iptc) && ($iptc["2#120"][0] != ""))
            $caption = $iptc["2#120"][0];
        }
        if ($dir != "") {
          $retval[] = array(
            "dir"      => "$dir",
            "fullname" => "$dir/$entry",
            "name"     => "$entry",
            "title"    => "$caption",
            "type"     => mime_content_type("./gallery/$dir/$entry"),
            "size"     => filesize("./gallery/$dir/$entry"),
            "lastmod"  => filemtime("./gallery/$dir/$entry")
          ); } else {
          $retval[] = array(
            "dir"      => "",
            "fullname" => "$entry",
            "name"     => "$entry",
            "title"    => "$caption",
            "type"     => mime_content_type("./gallery/$entry"),
            "size"     => filesize("./gallery/$entry"),
            "lastmod"  => filemtime("./gallery/$entry")
          ); }
      }
    }
    $d->close();

    sort($retval);

    return $retval;
}

function createThumbs( $pathToImages, $pathToThumbs, $thumbSize ) 
{
  // open the directory
  $dir = opendir($pathToImages);

  // loop through it, looking for any/all JPG files:
  while (false !== ($fname = readdir($dir))) {
    // parse path for the extension
    $info = pathinfo("$pathToImages/$fname");
    // continue only if this is a JPEG image
    if (strtolower($info['extension']) == 'jpg') 
    {
      // Check if thumbnail already exist
      if (file_exists("$pathToThumbs/$fname")) { continue; }

      // load image and get image size
      $img    = imagecreatefromjpeg("$pathToImages/$fname");
      $width  = imagesx($img);
      $height = imagesy($img);

      // calculate thumbnail size
      if ($width >= $height) {
        $new_width  = $thumbSize;
        $new_height = floor($height * ($thumbSize / $width));
      } else {
        $new_height = $thumbSize;
        $new_width  = floor($width  * ($thumbSize / $height));
      }

      // create a new temporary image
      $tmp_img = imagecreatetruecolor($new_width, $new_height);

      // copy and resize old image into new image 
      imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

      // save thumbnail into a file
      imagejpeg($tmp_img, "$pathToThumbs/$fname");
    }
  }
  // close the directory
  closedir($dir);
}

function cleanThumbs( $pathToImages, $pathToThumbs )
{
  echo "Cleaning $pathToThumbs\n";

  // open the directory
  $dir = opendir($pathToThumbs);

  // loop through it
  while (false !== ($fname = readdir($dir))) {
    if (is_dir("$pathToThumbs/$fname") && $fname != '.' && $fname != '..') {
      cleanThumbs("$pathToImages/$fname", "$pathToThumbs/$fname");
    } elseif (is_file("$pathToThumbs/$fname")) {
      // Check if file exist
      if (file_exists("$pathToImages/$fname")) { 
        continue; 
      } else {
        // Image not found, removing thumbnail
        echo "$pathToImages/$fname not found, removing thumbnail\n";
        unlink("$pathToThumbs/$fname");
      }
    }
  }
  closedir($dir);
  // Remove folder if empty
  $fcount = count(scandir($pathToThumbs));
  if ($fcount == 2) {
    echo "$pathToThumbs empty, removing\n";
    rmdir($pathToThumbs);
  } else {
    echo "$pathToThumbs contains $fcount elements\n";
  }
}

?>
