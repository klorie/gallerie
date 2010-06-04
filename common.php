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

    // array to hold return value
    $retval = array( "dir" => array(), "file" => array());

    // open pointer to directory and read list of files
    $d = @dir($basedir."/".$dir) or die("getFileList: Failed opening directory $basedir/$dir for reading");
    while(false !== ($entry = $d->read())) {
        // skip hidden files
        if($entry[0] == ".") continue;
        if(is_dir("$basedir/$dir/$entry")) {
            // Folder
            // Get caption from 00ALBUM if any
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
                        ); 
            } else {
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
            // File
            $info = pathinfo("$basedir/$dir/$entry");
            $ext = strtolower($info['extension']);
            if ($ext != 'jpg' && $ext != 'png' && $ext != 'gif' && $ext != 'bmp' ) { continue ;}
            $subtitle = "";
            $edate    = "";
            $esize    = "";
            if ($ext == 'jpg')
                $exif = exif_read_data("$basedir/$dir/$entry");
            else
                $exif = false;
            if ($exif != false) {
                if ($exif['DateTimeOriginal']) $edate = $exif['DateTimeOriginal'];
                if (empty($edate) && isset($exif['DateTime'])) $edate = $exif['DateTime'];
                if (!empty($edate)) {
                    $edate = split(':', str_replace(' ', ':', $edate));
                    $edate = "{$edate[0]}-{$edate[1]}-{$edate[2]} {$edate[3]}:{$edate[4]}:{$edate[5]}";
                    $edate = strftime('%d/%m/%Y %Hh%M', strtotime($edate));
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
                        ); 
            } else {
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

function createThumb($path, $fname)
{
    global $thumb_size;
    global $thumb_folder;
    global $image_folder;

    //Reset time limit to avoid timeout
    set_time_limit(30);

    $info = pathinfo("$image_folder/$path/$fname");
    $fname_noext = $info['filename'];
    // Fix for php < 5.2
    if ($fname_noext == "" ) {
        $fname_noext = substr($info['basename'], 0, strlen($info['basename'])-4);
    }

    // load image and get image size
    $img    = new Imagick();
    $img->ReadImage("$image_folder/$path/$fname");
    $width  = $img->GetImageWidth();
    $height = $img->GetImageHeight();

    // calculate thumbnail size
    if ($width >= $height) {
        $new_width  = $thumb_size;
        $new_height = $thumb_size * 0.75;
    } else {
        $new_height = $thumb_size;
        $new_width  = $thumb_size * 0.75;
    }

    $img->cropThumbnailImage($new_width, $new_height);
    $img->setImageFormat("jpeg");
    $img->setCompressionQuality(65);
    $img->setImageFilename("$thumb_folder/$path/$fname_noext".'.jpg');
    $img->WriteImage();
    $img->clear();
    $img->destroy();
}

function createResize($path, $fname)
{
    global $resize_width;
    global $resize_folder;
    global $image_folder;

    // Reset time limit to avoid timeout
    set_time_limit(30);

    $info = pathinfo("$image_folder/$path/$fname");
    $fname_noext = $info['filename'];
    // Fix for php < 5.2
    if ($fname_noext == "" ) {
        $fname_noext = substr($info['basename'], 0, strlen($info['basename'])-4);
    }

    // load image and get image size
    $img    = new Imagick();
    $img->ReadImage("$image_folder/$path/$fname");
    $width  = $img->GetImageWidth();
    $height = $img->GetImageHeight();

    if ($width >= $height) {
        $orientation = 0;
        $ratio = $width / $height;
    } else {
        $orientation = 1;
        $ratio = $height / $width;
    }

    $new_width = $resize_width;
    if ($orientation == 0) {
        $new_height = $new_width / $ratio;
    } else {
        $new_height = $new_width;
        $new_width  = $new_height / $ratio;
    }

    $img->thumbnailImage($new_width, $new_height);
    $img->setImageFormat("jpeg");
    $img->setCompressionQuality(65);
    $img->setImageFilename("$resize_folder/$path/$fname_noext".'.jpg');
    $img->WriteImage();
    $img->clear();
    $img->destroy();
}

function GetThumbsForDir($dir, $mode)
{
    global $thumb_folder;

    $found_thumb = 0;
    if(is_dir("$thumb_folder/$dir")) { 
        $subdirlist = getFileList($dir, true, 2, $thumb_folder);
        foreach($subdirlist[file] as $file_thumb) {
            if (preg_match("/ALBUM00/i", $file_thumb['name']) && ($mode == "ALBUM00")) {
                $found_thumb = 1;
                $tmp_fthumb  = $thumb_folder."/".$file_thumb['fullname'];
                break;
            }
            if ($mode != "ALBUM00") {
                $found_thumb = 1;
                $tmp_fthumb  = $thumb_folder."/".$file_thumb['fullname'];
                break;
            }
        }
        if (($mode == "RANDOM") && ($found_thumb == 1)) {
            $rand_keys  = rand(0,count($subdirlist[file])-1);
            $tmp_fthumb = $thumb_folder."/".$subdirlist[file][$rand_keys]['fullname'];
        }
    } 
    if ($found_thumb == 0) {
        $tmp_fthumb = "./images/nothumb.jpg"; 
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
function filemtime_r($path, $depth = 2)
{
    if (!file_exists($path))
        return time();

    if ((is_file($path) && ($depth > 0)) || ($depth == 0))
        return filemtime($path);

    $ret = 0;

    if ($depth > 0) {
        foreach (glob($path."/*") as $fn)
        {
            $subret = filemtime_r($fn, $depth - 1);
            if ($subret > $ret)
                $ret = $subret;   
        }
    }
    return $ret;   
}
?>
