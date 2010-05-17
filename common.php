<?php
require_once "config.php";

/**
 * determine the file mime type
 */
function mime_type($file) {
    if (stristr(PHP_OS, 'WIN')) { 
        $os = 'WIN';
    } else { 
        $os = PHP_OS;
    }
    $mime_type = '';

    if (function_exists('mime_content_type')) {
        $mime_type = mime_content_type($file);
    }
    
    // use PECL fileinfo to determine mime type
    if (!valid_src_mime_type($mime_type)) {
	    if (function_exists('finfo_open')) {
		    $finfo = @finfo_open(FILEINFO_MIME);
		    if ($finfo != '') {
			    $mime_type = finfo_file($finfo, $file);
			    finfo_close($finfo);
		    }
	    }
    }

    // try to determine mime type by using unix file command
    // this should not be executed on windows
    if (!valid_src_mime_type($mime_type) && $os != "WIN") {
        if (preg_match("/FREEBSD|LINUX/", $os)) {
		$mime_type = trim(@shell_exec('file -bi ' . escapeshellarg($file)));
        }
    }

    // use file's extension to determine mime type
    if (!valid_src_mime_type($mime_type)) {
        // set defaults
        $mime_type = 'image/png';
        // file details
        $fileDetails = pathinfo($file);
        $ext = strtolower($fileDetails["extension"]);
        // mime types
        $types = array(
             'jpg'  => 'image/jpeg',
             'jpeg' => 'image/jpeg',
             'png'  => 'image/png',
             'gif'  => 'image/gif'
         );
        
        if (strlen($ext) && strlen($types[$ext])) {
            $mime_type = $types[$ext];
        }
    }
    return $mime_type;
}

/**
 * 
 */
function valid_src_mime_type($mime_type) {

    if (preg_match("/jpg|jpeg|gif|png/i", $mime_type)) {
        return true;
    }
    
    return false;

}

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

function exif_get_lens(&$exif)
{
  if (!isset($exif['UndefinedTag:0x0095'])) return false;
  $lens = $exif['UndefinedTag:0x0095'];
  $lens = str_replace("EF-S", "", $lens);
  $lens = str_replace("EF", "", $lens);
  $lens = str_replace(" USM", "", $lens);
  if (strpos($lens, '28-75mm') !== false)
    $lens .= ' f/2.8';
  if ((strpos($lens, '70-200mm f/2.8L') !== false) && (strpos($lens, 'IS') === false))
    $lens = str_replace('L', 'EX', $lens);
  return $lens;
}

function exif_is_zoom(&$exif)
{
  // Return true when failed, because we do not print used focal when fixed focal
  if (!isset($exif['UndefinedTag:0x0095'])) return true;
  return (strpos($exif['UndefinedTag:0x0095'], '-') !== false);
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
    global $reverse_subalbum_sort;

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
        $edate    = "";
	$esize    = "";
        $exif = exif_read_data("$basedir/$dir/$entry");
        if ($exif != false) {
          if ($exif['DateTimeOriginal']) $edate = $exif['DateTimeOriginal'];
          if (empty($edate) && isset($exif['DateTime'])) $edate = $exif['DateTime'];
          if (!empty($edate)) {
            $edate = split(':', str_replace(' ', ':', $edate));
            $edate = "{$edate[0]}-{$edate[1]}-{$edate[2]} {$edate[3]}:{$edate[4]}:{$edate[5]}";
            $edate = strftime('%d/%m/%Y %H:%M', strtotime($edate));
          }
          if ($exif['COMPUTED']['Width'] && $exif['COMPUTED']['Height']) $esize = $exif['COMPUTED']['Width']."x".$exif['COMPUTED']['Height'];
          if ($exif['Model']) $subtitle .= $exif['Model']." - ";
          if ($exif['ISOSpeedRatings']) $subtitle .= $exif['ISOSpeedRatings']."ISO, ";
          if (exif_get_lens($exif)) $subtitle .= exif_get_lens($exif);
          if (exif_get_lens($exif) && exif_is_zoom($exif)) $subtitle .= " @ ";
          if (exif_get_focal($exif) && exif_is_zoom($exif)) $subtitle .= exif_get_focal($exif);
          if (exif_get_lens($exif) || exif_get_focal($exif)) $subtitle .= ", ";
          if (exif_get_shutter($exif)) $subtitle .= exif_get_shutter($exif).", ";
          if (exif_get_fstop($exif)) $subtitle .= exif_get_fstop($exif);
        }
	if ($subtitle !== "") $subtitle .= "<br />";
        $caption = $info['filename'];
        $caption = strtr($entry, "_", " ");
        $tags = "";
        $size = getimagesize("$basedir/$dir/$entry", $imginfo);
	if (isset($imginfo["APP13"])) {
          $iptc = iptcparse($imginfo["APP13"]);
          if (is_array($iptc) && ($iptc["2#120"][0] != ""))
            $caption = $iptc["2#120"][0];
          if (is_array($iptc) && ($iptc["2#025"][0] != ""))
            for ($t = 0; $t < count($iptc["2#025"]); $t++) {
              $tags .= $iptc["2#025"][$t];
              if ($t < (count($iptc["2#025"]) - 1)) $tags .= ", ";
            }
        }
        if ($dir != "") {
          if (empty($edate)) $edate = strftime('%d/%m/%Y %H:%M', filemtime("$basedir/$dir/$entry"));
          if (filesize("$basedir/$dir/$entry") >= (1024*1024))
            $esize = sprintf("(%.1fM, $esize)",(filesize("$basedir/$dir/$entry") / (1024*1024)));
          else
            $esize = sprintf("(%dK, $esize)",(filesize("$basedir/$dir/$entry") / 1024));
          $retval[file][] = array(
            "dir"      => "$dir",
            "fullname" => "$dir/$entry",
            "name"     => "$entry",
            "title"    => "$caption",
            "subtitle" => "$subtitle",
            "type"     => mime_type("$basedir/$dir/$entry"),
            "tags"     => "$tags",
            "size"     => "$esize",
            "lastmod"  => "$edate"
          ); } else {
          if (empty($edate)) $edate = strftime('%d/%m/%Y %H:%M', filemtime("$basedir/$entry"));
          if (filesize("$basedir/$entry") >= (1024*1024))
            $esize = sprintf("(%.1fM, $esize)",(filesize("$basedir/$entry") / (1024*1024)));
          else
            $esize = sprintf("(%dK, $esize)",(filesize("$basedir/$entry") / 1024));
          $retval[file][] = array(
            "dir"      => "",
            "fullname" => "$entry",
            "name"     => "$entry",
            "title"    => "$caption",
            "subtitle" => "$subtitle",
            "type"     => mime_type("$basedir/$entry"),
            "tags"     => "$tags",
            "size"     => "$esize",
            "lastmod"  => "$edate"
          ); }
      }
    }
    $d->close();
    if (($dir == "") || ($reverse_subalbum_sort == 0))
      asort($retval[dir]);
    else
      arsort($retval[dir]);
    sort($retval[file]);
    //echo count($retval[dir],0);
    return $retval;
}

function createThumbs( $path )
{
	global $thumb_folder;
	global $image_folder;

	// open the directory
	$dir = opendir("$image_folder/$path");

	// loop through it, looking for any/all JPG files:
	while (false !== ($fname = readdir($dir))) {
		// parse path for the extension
		$info = pathinfo("$image_folder/$path/$fname");
		$ext = strtolower($info['extension']);
		$fname_noext = $info['filename'];

		if ( $ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'bmp')
		{
			// Check if thumbnail already exist (whatever extension)
			if (count(glob("$thumb_folder/$path/$fname_noext.*")) === 0) 
				createSingleThumb($path, $fname);
		}
	}
	// close the directory
	closedir($dir);
}

function createSingleThumb($path, $fname)
{
	global $thumb_bgcolor;
	global $thumb_size;
	global $thumb_create;
	global $thumb_folder;
	global $image_folder;

	//Reset time limit to avoid timeout
	set_time_limit(30);

        $info = pathinfo("$image_folder/$path/$fname");
        $fname_noext = $info['filename'];

	// load image and get image size
	$img    = new Imagick();
	$img->ReadImage("$image_folder/$path/$fname");
	$width  = $img->GetImageWidth();
	$height = $img->GetImageHeight();

	// calculate thumbnail size
	if ($width >= $height) {
		$new_width  = $thumb_size - 12;
		$new_height = floor($height * (($thumb_size-12) / $width));
	} else {
		$new_height = $thumb_size - 12;
		$new_width  = floor($width  * (($thumb_size-12) / $height));
	}

	$img->thumbnailImage( $new_width, $new_height );
	$img->roundCorners( 5, 5 );

	$shadow = $img->clone();
	$shadow->setImageBackgroundColor(new ImagickPixel('black'));
	$shadow->shadowImage(80, 3, 5, 5);
	$shadow->compositeImage($img, Imagick::COMPOSITE_OVER, 0, 0);
        if ($thumb_create == 'png') {
		$shadow->setImageFileName("$thumb_folder/$path/$fname_noext.$thumb_create");
		$shadow->setImageFormat("png");
		$shadow->setImageDepth(8);
		$shadow->setImageInterlaceScheme(Imagick::INTERLACE_PNG);
		$shadow->setImageCompressionQuality(95); 
		$shadow->WriteImage();  
		$shadow->clear();
		$shadow->destroy();
	} else {		
	        $bg = $shadow->clone();
	        $fillcolor   = new ImagickPixel($thumb_bgcolor);
	        $bordercolor = new ImagickPixel("#777777");
	        $bg->colorFloodFillImage($fillcolor, 100, $bordercolor, 0, 0);
	        $bg->compositeImage($shadow, Imagick::COMPOSITE_OVER, 0, 0);
	        $bg->setImageFormat("jpeg");
	        $bg->setCompressionQuality(90);
	        $bg->flattenImages();
	        $bg->setImageFileName("$thumb_folder/$path/$fname_noext.$thumb_create");
	        $bg->WriteImage();
		$bg->clear();
		$bg->destroy();
	}
	$shadow->clear();
	$shadow->destroy();
	$img->clear();
	$img->destroy();
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

// Returns the date of the most recently modified file inside the folder
function filemtime_r($path)
{
	if (!file_exists($path))
		return 0;

	if (is_file($path))
		return filemtime($path);
	$ret = 0;

	foreach (glob($path."/*") as $fn)
	{
		if (filemtime_r($fn) > $ret)
			$ret = filemtime_r($fn);   
	}
	return $ret;   
}

?>
