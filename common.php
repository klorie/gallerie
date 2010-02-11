<?php
function exif_get_float($value)
{
  $pos = strpos($value, '/');
  if ($pos === false) return (float) $value;
  $a = (float) substr($value, 0, $pos);
  $b = (float) substr($value, $pos+1);
  return ($b == 0) ? ($a) : ($a / $b);
}

function exif_get_shutter(&$exif) 
{
  if (!isset($exif['ExposureTime'])) return false;
  $shutter = exif_get_float($exif['ExposureTime']);
  if ($shutter == 0) return false;
  if ($shutter >= 1) return round($shutter) . 's';
  return '1/' . round(1 / $shutter) . 's';
}

function exif_get_fstop(&$exif) 
{
  if (!isset($exif['FNumber'])) return false;
  $fstop = exif_get_float($exif['FNumber']);
  if ($fstop == 0) return false;
  return 'f/' . round($fstop,1);
} 

function exif_get_focal(&$exif)
{
  if (!isset($exif['FocalLength'])) return false;
  $focal  = exif_get_float($exif['FocalLength']);
  if ($focal == 0) return false;
  return round($focal,1).'mm';
}

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

function getFileList($dir, $recurse=false, $depth=false, $basedir="./gallery")
{
    # array to hold return value
    $retval = array( "dir" => array(), "file" => array());

    # open pointer to directory and read list of files
    $d = @dir($basedir."/".$dir) or die("getFileList: Failed opening directory $basedir/$dir for reading");
    while(false !== ($entry = $d->read())) {
      # skip hidden files
      if($entry[0] == ".") continue;
      if(is_dir("$basedir/$dir/$entry")) {
	# Folder
        # Get caption from 00ALBUM if any
        if (file_exists("$basedir/$dir/$entry/00ALBUM.jpg")) {
          $caption_file = "$basedir/$dir/$entry/00ALBUM.jpg";
        } elseif (file_exists("$basedir/$dir/$entry/00ALBUM.JPG")) {
          $caption_file = "$basedir/$dir/$entry/00ALBUM.JPG";
        } else {
          $caption_file = "";
        }
        if ($caption_file != "") {
          $exif = exif_read_data($caption_file);
          if ($exif != false) {
            $dir_caption = $exif["COMPUTED"]["UserComment"];
            if ($dir_caption == "") {
              $dir_caption = strtr($entry, "_", " ");
            }
          } else {
            $dir_caption = strtr($entry, "_", " ");
          }
        } else {
          $dir_caption = strtr($entry, "_", " ");
        }

	if ($dir != "") {
          $retval[dir][] = array(
            "dir"      => "$dir",
            "fullname" => "$dir/$entry",
            "name"     => "$entry",
            "type"     => "dir",
            "title"    => "$dir_caption",
            "size"     => 0,
            "lastmod"  => filemtime("$basedir/$dir/$entry")
          ); } else {
          $retval[dir][] = array(
            "dir"      => "",
            "fullname" => "$entry",
            "name"     => "$entry",
            "type"     => "dir",
            "title"    => "$dir_caption",
            "size"     => 0,
            "lastmod"  => filemtime("$basedir/$entry")
          ); }
        if($recurse && is_readable("$basedir/$dir/$entry")) {
          if($depth === false) {
            $retval = array_merge_recursive($retval, getFileList("$dir/$entry", true, false, $basedir));
          } elseif($depth > 0) {
            $retval = array_merge_recursive($retval, getFileList("$dir/$entry", true, $depth - 1, $basedir));
          }
        }
      } elseif(is_readable("$basedir/$dir/$entry")) {
	# File
	$info = pathinfo("$basedir/$dir/$entry");
	$ext = strtolower($info['extension']);
	if ($ext != 'jpg' && $ext != 'png' && $ext != 'gif' && $ext != 'bmp' ) { continue ;}
        $subtitle = "";
        $exif = exif_read_data("$basedir/$dir/$entry");
        if ($exif != false) {
          if ($exif['Model']) $subtitle .= $exif['Model']." - ";
          if ($exif['ISOSpeedRatings']) $subtitle .= $exif['ISOSpeedRatings']."ISO, ";
          if (exif_get_focal($exif)) $subtitle .= exif_get_focal($exif).", ";
          if (exif_get_shutter($exif)) $subtitle .= exif_get_shutter($exif).", ";
          if (exif_get_fstop($exif)) $subtitle .= exif_get_fstop($exif);
        }
	if ($subtitle !== "") $subtitle .= "<br />";
        $caption = "$entry";
        $size = getimagesize("$basedir/$dir/$entry", $imginfo);
	if (isset($imginfo["APP13"])) {
          $iptc = iptcparse($imginfo["APP13"]);
          if (is_array($iptc) && ($iptc["2#120"][0] != ""))
            $caption = $iptc["2#120"][0];
        }
        if ($dir != "") {
          $retval[file][] = array(
            "dir"      => "$dir",
            "fullname" => "$dir/$entry",
            "name"     => "$entry",
            "title"    => "$caption",
            "subtitle" => "$subtitle",
            "type"     => mime_content_type("$basedir/$dir/$entry"),
            "size"     => filesize("$basedir/$dir/$entry"),
            "lastmod"  => filemtime("$basedir/$dir/$entry")
          ); } else {
          $retval[file][] = array(
            "dir"      => "",
            "fullname" => "$entry",
            "name"     => "$entry",
            "title"    => "$caption",
            "subtitle" => "$subtitle",
            "type"     => mime_content_type("$basedir/$entry"),
            "size"     => filesize("$basedir/$entry"),
            "lastmod"  => filemtime("$basedir/$entry")
          ); }
      }
    }
    $d->close();
    sort($retval[dir]);
    sort($retval[file]);
    //echo count($retval[dir],0);
    return $retval;
}

function createThumbsJPG( $pathToImages, $pathToThumbs, $thumbSize )
{
  require("config.php");

  // open the directory
  $dir = opendir($pathToImages);

  // loop through it, looking for any/all JPG files:
  while (false !== ($fname = readdir($dir))) {
    // parse path for the extension
    $info = pathinfo("$pathToImages/$fname");
    $ext = strtolower($info['extension']);
    $fname_noext = $info['filename'];

    if ( $ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'bmp')
      {
      // Check if thumbnail already exist (whatever extension)
      if (count(glob("$pathToThumbs/$fname_noext.*")) != 0) { continue; }

      //Reset time limit to avoid timeout
      set_time_limit(30);

      // load image and get image size
      $img    = new Imagick();
      $img->ReadImage("$pathToImages/$fname");
      $width  = $img->GetImageWidth();
      $height = $img->GetImageHeight();

      // calculate thumbnail size
      if ($width >= $height) {
        $new_width  = $thumbSize-12;
        $new_height = floor($height * (($thumbSize-12) / $width));
      } else {
        $new_height = $thumbSize-12;
        $new_width  = floor($width  * (($thumbSize-12) / $height));
      }

      $img->thumbnailImage( $new_width, $new_height );
      $img->roundCorners( 5, 5 );

      $shadow = $img->clone();
      $shadow->setImageBackgroundColor(new ImagickPixel('black'));
      $shadow->shadowImage(80, 3, 5, 5);
      $shadow->compositeImage($img, Imagick::COMPOSITE_OVER, 0, 0);
      $bg = $shadow->clone();
      $fillcolor   = new ImagickPixel($thumb_bgcolor);
      $bordercolor = new ImagickPixel("#777777");
      $bg->colorFloodFillImage($fillcolor, 100, $bordercolor, 0, 0);
      $bg->compositeImage($shadow, Imagick::COMPOSITE_OVER, 0, 0);
      $bg->setImageFormat("jpeg");
      $bg->setCompressionQuality(90);
      $bg->flattenImages();
      $bg->setImageFileName("$pathToThumbs/$fname_noext".".jpg");
      $bg->WriteImage();
      $img->clear();
      $img->destroy();
      $shadow->clear();
      $shadow->destroy();
      $bg->clear();
      $bg->destroy();
    }
  }
  // close the directory
  closedir($dir);
}

function createThumbsPNG( $pathToImages, $pathToThumbs, $thumbSize )
{
  require("config.php");

  // open the directory
  $dir = opendir($pathToImages);
 
  // loop through it, looking for any/all JPG files:
  while (false !== ($fname = readdir($dir))) {
    // parse path for the extension
    $info = pathinfo("$pathToImages/$fname");
    $ext = strtolower($info['extension']);
    $fname_noext = $info['filename'];

    if ( $ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'bmp')
      {
      // Check if thumbnail already exist (whatever extension)
      if (count(glob("$pathToThumbs/$fname_noext.*")) != 0) { continue; }

      //Reset time limit to avoid timeout
      set_time_limit(30);

      // load image and get image size
      $img    = new Imagick();
      $img->ReadImage("$pathToImages/$fname");
      $width  = $img->GetImageWidth();
      $height = $img->GetImageHeight();

      // calculate thumbnail size
      if ($width >= $height) {
        $new_width  = $thumbSize-12;
        $new_height = floor($height * (($thumbSize-12) / $width));
      } else {
        $new_height = $thumbSize-12;
        $new_width  = floor($width  * (($thumbSize-12) / $height));
      }
      
      $img->thumbnailImage( $new_width, $new_height );
      $img->setImageFormat("png"); 
      $img->roundCorners( 5, 5 );
      
      $shadow = $img->clone();
      $shadow->setImageBackgroundColor(new ImagickPixel('black'));
      $shadow->shadowImage(80, 3, 5, 5);
      $shadow->compositeImage($img, Imagick::COMPOSITE_OVER, 0, 0);
      $shadow->setImageFileName("$pathToThumbs/$fname_noext".".png");
      $shadow->setImageDepth(8);
      $shadow->setImageInterlaceScheme(Imagick::INTERLACE_PNG);
      $shadow->setImageCompressionQuality(95); 
 
      // save thumbnail into a file
      $shadow->WriteImage();  
      $img->clear();
      $img->destroy();
      $shadow->clear();
      $shadow->destroy();
    }
  }
  // close the directory
  closedir($dir);
}

function cleanThumbs( $pathToImages, $pathToThumbs )
{
  echo "Cleaning $pathToThumbs<\br>\n";

  // open the directory
  $dir = opendir($pathToThumbs);

  // loop through it
  while (false !== ($fname = readdir($dir))) {
    if (is_dir("$pathToThumbs/$fname") && $fname != '.' && $fname != '..') {
      cleanThumbs("$pathToImages/$fname", "$pathToThumbs/$fname");
    } elseif (is_file("$pathToThumbs/$fname")) {
      // Check if file exist
      $info = pathinfo("$pathToThumbs/$fname");
      $fname_noext = $info['filename'];
      
      if (count(glob("$pathToImages/$fname_noext.*")) != 0)
      { 
        continue; 
      } else {
        // Image not found, removing thumbnail
        echo "$pathToImages/$fname_noext not found, removing thumbnail\n";
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

function GetThumbsForDir( $dir, $mode )
{
    $found_thumb = 0;
    if(is_dir("./thumbnails/$dir")) { 
       $subdirlist = getFileList($dir, true, 10, "./thumbnails");
       foreach($subdirlist[file] as $file_thumb) {
	 if ( preg_match("/ALBUM00/i", $file_thumb[name]) && $mode == "ALBUM00") {
           $found_thumb = 1;
           $tmp_fthumb = "./thumbnails/".$file_thumb[fullname];
	   break;
	 }
         if ($mode != "ALBUM00") {
           $found_thumb = 1;
	   $tmp_fthumb = "./thumbnails/".$file_thumb[fullname];
           break;
         }
       }
       if ($mode == "RANDOM" && $found_thumb == 1) {
         $rand_keys = rand(0,count($subdirlist[file])-1);
         $tmp_fthumb = "./thumbnails/".$subdirlist[file][$rand_keys]['fullname'];
       }
    } 
    if ($found_thumb == 0) {
      $tmp_fthumb = "./images/nothumb.png"; 
    }

   return $tmp_fthumb;
}
function deleteDir($dir) 
{ 
   if (substr($dir, strlen($dir)-1, 1) != '/') 
       $dir .= '/'; 
   if ($handle = opendir($dir)) 
   { 
       while ($obj = readdir($handle)) 
       { 
           if ($obj != '.' && $obj != '..') 
           { 
               if (is_dir($dir.$obj)) 
               { 
                   if (!deleteDir($dir.$obj)) 
                       return false; 
               } 
               elseif (is_file($dir.$obj)) 
               { 
                   if (!unlink($dir.$obj)) 
                       return false; 
               } 
           } 
       } 
       closedir($handle); 
       if (!@rmdir($dir)) 
           return false; 
       return true; 
   } 
   return false; 
} 
?>
