<?php

require_once "config.php";

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
    return round($focal,1);
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

function exif_get_latitude(&$exif)
{
    if (!isset($exif['GPS']['GPSLatitude'])) return false;
    $lat_degrees = exif_get_float($exif['GPS']['GPSLatitude'][0]);
    $lat_minutes = exif_get_float($exif['GPS']['GPSLatitude'][1]);
    $lat_seconds = exif_get_float($exif['GPS']['GPSLatitude'][2]);
    $lat_hemisph = $exif['GPS']['GPSLatitudeRef'];
    $lat_decimal = $lat_degrees + ($lat_minutes / 60) + ($lat_seconds / 3600);
    if ($lat_hemisph == 'S' || $lat_hemisph == 'W') $lat_decimal *= -1;

    return $lat_decimal;
}

function exif_get_longitude(&$exif)
{
    if (!isset($exif['GPS']['GPSLongitude'])) return false;
    $lon_degrees = exif_get_float($exif['GPS']['GPSLongitude'][0]);
    $lon_minutes = exif_get_float($exif['GPS']['GPSLongitude'][1]);
    $lon_seconds = exif_get_float($exif['GPS']['GPSLongitude'][2]);
    $lon_hemisph = $exif['GPS']['GPSLongitudeRef'];
    $lon_decimal = $lon_degrees + ($lon_minutes / 60) + ($lon_seconds / 3600);
    if ($lon_hemisph == 'S' || $lon_hemisph == 'W') $lon_decimal *= -1;

    return $lon_decimal;
}

class mediaFolder
{
    public $parent    = NULL;
    public $db_id     = -1;
    public $title     = "";
    public $lastmod   = "";
    public $name      = "";
    public $thumbnail = "";
    public $subfolder = array();
    public $element   = array();

    function __construct(mediaFolder $parent = NULL)
    {
        $this->parent = $parent;
    }

    function fullname()
    {
        $fullname = $this->name;
        $parent   = $this->parent;
        while($parent != NULL) {
            $fullname = $this->parent->name.'/'.$fullname;
            $parent   = $parent->parent;
        }
        return $fullname;
    }

    function loadFromPath($source_path)
    {
        global $image_folder;

        $this->name      = $source_path;
        if ($source_path != "")
            $source_fullpath = $image_folder.'/'.$this->fullname();
        else
            $source_fullpath = $image_folder;

        $dir = @dir($source_fullpath) or die("Failed to open $source_fullpath for reading");
        $this->lastmod = strftime('%d/%m/%Y %H:%M:%S', filemtime($source_fullpath));

        while(($entry = $dir->read()) !== false) {
            // skip hidden files
            if ($entry[0] == '.') continue;
            if (is_dir("$source_fullpath/$entry")) {
                $subfolder = new mediaFolder($this);
                $subfolder->loadFromPath($entry);
                $this->subfolder[] = $subfolder;
            } else if ($entry == 'folder.jpg') {
                // Found folder thumbnail
                $this->thumbnail = $entry;
                $exif = exif_read_data($entry);
                if ($exif != false && isset($exif['COMPUTED']['UserComment'])) {
                    $this->title = $exif["COMPUTED"]["UserComment"];
                    if ($this->title == "") {
                        $this->title = strtr($entry, "_", " ");
                    }
                } else {
                    $title = strtr($entry, "_", " ");
                }
            } else {
                $info = pathinfo("$source_fullpath/$entry");
                $ext  = strtolower($info['extension']);
                if ($ext != 'jpg' && $ext != 'png' && $ext != 'gif' && $ext != 'bmp' && $ext != 'avi' && $ext != 'mov' && $ext != 'mpg') continue;
                $element = new mediaObject($this);
                $element->loadFromFile($entry);
                $this->element[] = $element;
            }
        }
        if ($this->thumbnail == "") {
            $this->title = strtr($source_path, "_", " ");
            if (count($this->element) > 0)
                $this->thumbnail = $this->element[0]->filename;
            else if (count($this->subfolder) > 0)
                $this->thumbnail = $this->subfolder[0]->thumbnail;
        }
    }
}

class mediaObject
{
    public $db_id        = -1;
    public $folder       = NULL;
    public $type         = "";
    public $tags         = array();
    public $title        = "";
    public $filesize     = -1;
    public $duration     = "";
    public $lastmod      = "";
    public $filename     = "";
    public $camera       = "";
    public $focal        = -1;
    public $lens         = "";
    public $fstop        = "";
    public $shutter      = "";
    public $iso          = -1;
    public $originaldate = "";
    public $width        = -1;
    public $height       = -1;
    public $lens_is_zoom = -1;
    public $longitude    =  9999.0;
    public $latitude     =  9999.0;
    public $altitude     = -9999.0;

    function __construct(mediaFolder $folder = NULL)
    {
        $this->folder = $folder;
    }

    function loadFromFile($source_filename)
    {
        global $image_folder;

        if ($this->folder != NULL)
            $source_fullname = $image_folder.'/'.$this->folder->fullname().'/'.$source_filename;
        else
            $source_fullname = $image_folder.'/'.$source_filename;
        $this->filename = $source_filename;
        $this->lastmod  = strftime('%d/%m/%Y %H:%M:%S', filemtime($source_fullname));
        $info = pathinfo($source_fullname);
        $ext  = strtolower($info['extension']);
        if ($ext == 'jpg' || $ext == 'png' || $ext == 'gif' || $ext == 'bmp')
            $this->type = 'picture';
        else if ($ext == 'mov' || $ext == 'mpg' || $ext == 'avi')
            $this->type = 'movie';
        else
            throw new Exception('Unsupported file type !');

        if ($ext == 'jpg')
            $exif = exif_read_data($source_fullname);
        else
            $exif = false;

        if ($exif != false) {
            if (isset($exif['DateTimeOriginal'])) $edate = $exif['DateTimeOriginal'];
            if (empty($edate) && isset($exif['DateTime'])) $edate = $exif['DateTime'];
            if (!empty($edate)) {
                $edate = split(':', str_replace(' ', ':', $edate));
                $edate = "{$edate[0]}-{$edate[1]}-{$edate[2]} {$edate[3]}:{$edate[4]}:{$edate[5]}";
                $edate = strftime('%d/%m/%Y %H:%M:%S', strtotime($edate));
                $this->originaldate = $edate;
            }
            if ($exif['COMPUTED']['Width'] && $exif['COMPUTED']['Height']) { 
                $this->width  = $exif['COMPUTED']['Width'];
                $this->height = $exif['COMPUTED']['Height'];
            }
            if (isset($exif['Model'])) $this->camera                = $exif['Model'];
            if (isset($exif['ISOSpeedRatings'])) $this->iso         = $exif['ISOSpeedRatings'];
            if (exif_get_lens($exif)) $this->lens                   = exif_get_lens($exif);
            $this->lens_is_zoom                                     = exif_is_zoom($exif);
            if (exif_get_focal($exif)) $this->focal                 = exif_get_focal($exif);
            if (exif_get_shutter($exif)) $this->shutter             = exif_get_shutter($exif);
            if (exif_get_fstop($exif)) $this->fstop                 = exif_get_fstop($exif);
            if (exif_get_latitude($exif)) $this->latitude           = exif_get_latitude($exif);
            if (exif_get_longitude($exif)) $this->longitude         = exif_get_longitude($exif);
            if (isset($exif['GPS']['GPSAltitude'])) $this->altitude = $exif['GPS']['GPSAltitude'][0];
        }
        if ($this->originaldate == "") $this->originaldate = strftime('%d/%m/%Y %H:%M:%S', filemtime($source_fullname));
        $this->title = $info['filename'];
        $this->title = strtr($this->title, "_", " ");
        if ($this->type == 'picture') {
            $size = getimagesize($source_fullname, $imginfo);
            if (isset($imginfo["APP13"])) {
                $iptc = iptcparse($imginfo["APP13"]);
                if (is_array($iptc) && isset($iptc["2#120"][0]))
                    $this->title = $iptc["2#120"][0];
                if (is_array($iptc) && ($iptc["2#025"][0] != ""))
                    for ($t = 0; $t < count($iptc["2#025"]); $t++) {
                        $this->tags[] = $iptc["2#025"][$t];
                    }
            }
        } else {
            if (extension_loaded('ffmpeg')) {
                // Get video length
                $movie = new ffmpeg_movie($source_fullname);
                $duration = $movie->getDuration();
                if ($duration > 60)
                    $this->duration = sprintf("~%dmin%ds", $duration / 60, $duration % 60);
                else
                    $this->duration = sprintf("~%ds", $duration);
            }
        }
        $this->filesize = filesize($source_fullname);
    }
}

?>
