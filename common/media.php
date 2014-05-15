<?php

//! Retrieve float value from an EXIF field
function exif_get_float($value)
{
    $pos   = strpos($value, '/');
    if ($pos === false) return (float) $value;
    $a = (float) substr($value, 0, $pos);
    $b = (float) substr($value, $pos+1);
    return ($b == 0) ? ($a) : ($a / $b);
}

//! Convert EXIF ExposureTime field to a human-readable shutter value (in 1/x)
function exif_get_shutter(&$exif) 
{
    if (!isset($exif['ExposureTime'])) return false;
    $shutter = exif_get_float($exif['ExposureTime']);
    if ($shutter == 0) return false;
    if ($shutter >= 1) return round($shutter) . 's';
    return '1/' . round(1 / $shutter) . 's';
}

//! Convert EXIF FNumber field to human-readable focal notation (f/x)
function exif_get_fstop(&$exif) 
{
    if (!isset($exif['FNumber'])) return false;
    $fstop = exif_get_float($exif['FNumber']);
    if ($fstop == 0) return false;
    return 'f/' . number_format($fstop,1,'.','');
} 

//! Retrieve EXIF Focal length if available
function exif_get_focal(&$exif)
{
    if (!isset($exif['FocalLength'])) return false;
    $focal  = exif_get_float($exif['FocalLength']);
    if ($focal == 0) return false;
    return round($focal);
}

//! Return Lens name with local modifications (for Tamron and Sigma Lenses)
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

//! Decode EXIF Zoom flag (at least for Canon)
function exif_is_zoom(&$exif)
{
    if (!isset($exif['UndefinedTag:0x0095']))
        return 0;
    else if (strpos($exif['UndefinedTag:0x0095'], '-') !== false) {
        return 1;
    } else {
        return 0;
    }

}

//! Return EXIF Latitude field in google-usable format
function exif_get_latitude(&$exif)
{
    if (!isset($exif['GPSLatitude'])) return false;
    $lat_degrees = exif_get_float($exif['GPSLatitude'][0]);
    $lat_minutes = exif_get_float($exif['GPSLatitude'][1]);
    $lat_seconds = exif_get_float($exif['GPSLatitude'][2]);
    $lat_hemisph = $exif['GPSLatitudeRef'];
    $lat_decimal = $lat_degrees + ($lat_minutes / 60) + ($lat_seconds / 3600);
    if ($lat_hemisph == 'S' || $lat_hemisph == 'W') $lat_decimal *= -1;

    return $lat_decimal;
}

//! Returns EXIF Longitutde in google-usable format
function exif_get_longitude(&$exif)
{
    if (!isset($exif['GPSLongitude'])) return false;
    $lon_degrees = exif_get_float($exif['GPSLongitude'][0]);
    $lon_minutes = exif_get_float($exif['GPSLongitude'][1]);
    $lon_seconds = exif_get_float($exif['GPSLongitude'][2]);
    $lon_hemisph = $exif['GPSLongitudeRef'];
    $lon_decimal = $lon_degrees + ($lon_minutes / 60) + ($lon_seconds / 3600);
    if ($lon_hemisph == 'S' || $lon_hemisph == 'W') $lon_decimal *= -1;

    return $lon_decimal;
}

/**
 * mediaFolder class manages folders (e.g. albums) with subfolders and elements
 */
class mediaFolder
{
    public $lastupdate   = NULL;    //!< Database last update timestamp
    public $parent       = NULL;    //!< Reference to parent folder if any
    public $db_id        = -1;      //!< ID in corresponding database table
    public $title        = "";      //!< Album Title
    public $originaldate = "";      //!< Album Date (e.g. date of event)
    public $lastmod      = "";      //!< Album Last modification (elements updated/added)
    public $name         = "";      //!< Album Folder Name
    public $thumbnail    = "";      //!< Thumbnail Name
    public $subfolder    = array(); //!< List of subfolder(s)
    public $element      = array(); //!< List of element(s)

    //! mediaFolder constructor only registers reference to the parent album
    function __construct(mediaFolder $parent = NULL)
    {
        $this->parent = $parent;
        if ($parent != NULL)
            $this->lastupdate = $parent->lastupdate;
    }

    //! Return subfolders count
    /*! \param recursive flag control if we include any further subfolder leverls in the count */
    function getSubFolderCount($recursive = true)
    {
        $subfoldercount = count($this->subfolder);

        if($recursive == true) {
            foreach($this->subfolder as $subfolder) {
                $subfoldercount += $subfolder->getSubFolderCount();
            }
        }

        return $subfoldercount;
    }

    //! Return folder path from the top (starting right after $image_folder)
    function fullname()
    {
        $fullname = $this->name;
        $parent   = $this->parent;
        while($parent != NULL) {
            if ($parent->name != "")
                $fullname = $parent->name.'/'.$fullname;
            $parent   = $parent->parent;
        }
        return $fullname;
    }

    //! Load all elements and subfolders available in the given path (relative to $image_folder)
    function loadFromPath($source_path = "", $recursive = true)
    {
        global $BASE_DIR;
        global $image_folder;
        global $folder_thumbname;

        $edate = "";

        $this->name      = $source_path;
        if ($source_path != "")
            $source_fullpath = "$BASE_DIR/$image_folder/".$this->fullname();
        else
            $source_fullpath = "$BASE_DIR/$image_folder";
        $source_fullpath = realpath($source_fullpath);

        $current_dir_list = scandir($source_fullpath);
        if ($current_dir_list === false) throw new Exception("-E- Failed to open $source_fullpath for reading");

        // Reset modification time to very old value when no elements in the folder
        $this->lastmod = '1970/01/01 00:00:00';

        foreach($current_dir_list as $entry) {
            // skip hidden files
            if ($entry[0] == '.') continue;
            if (is_dir("$source_fullpath/$entry")) {
                // Subfolder
                $subfolder = new mediaFolder($this);
                $subfolder->name = $entry;
                if ($recursive == true)
                    $subfolder->loadFromPath($entry, true);
                $this->subfolder[] = $subfolder;
                // Update modification time according to most recent element in folder
                if (strtotime($subfolder->lastmod) > strtotime($this->lastmod))
                    $this->lastmod = $subfolder->lastmod;
            } else if ($entry == $folder_thumbname) {
                // Found folder thumbnail
                $this->thumbnail = $entry;
                $exif = exif_read_data("$source_fullpath/$entry");
                if ($exif != false) {
                    if (isset($exif['DateTimeOriginal'])) $edate = $exif['DateTimeOriginal'];
                    if (empty($edate) && isset($exif['DateTime'])) $edate = $exif['DateTime'];
                    if (isset($exif['COMPUTED']['UserComment'])) {
                        $this->title = $exif["COMPUTED"]["UserComment"];
                    }
                }
                if (!empty($edate)) {
                    $edate = explode(':', str_replace(' ', ':', $edate));
                    if (count($edate) > 5) {
                        $edate = "{$edate[0]}-{$edate[1]}-{$edate[2]} {$edate[3]}:{$edate[4]}:{$edate[5]}";
                        $edate = strftime('%Y/%m/%d %H:%M:%S', strtotime($edate));
                        $this->originaldate = $edate;
                    }
                }
                if ($this->title == "")
                    $this->title = strtr($source_path, "_", " ");
            } else {
                // Plain file
                $ext = strtolower(pathinfo("$source_fullpath/$entry", PATHINFO_EXTENSION));
                if (($ext == "") || ($ext != 'jpg' && $ext != 'png' && $ext != 'gif' && $ext != 'bmp' && $ext != 'avi' && $ext != 'mov' && $ext != 'mpg')) {
                    continue;
                }
                if (strtotime($this->lastupdate) > filemtime("$source_fullpath/$entry")) continue;
                $element = new mediaObject($this);
                $element->loadFromFile($entry);
                $this->element[] = $element;
                // Update modification time according to most recent element in folder
                if (strtotime($element->lastmod) > strtotime($this->lastmod))
                    $this->lastmod = $element->lastmod;
            }
        }
        if ($this->thumbnail == "") {
            $this->title = strtr($source_path, "_", " ");
            if (count($this->element) > 0) {
                $this->thumbnail    = $this->element[0]->filename;
                $this->originaldate = $this->element[0]->originaldate;
            } else if (count($this->subfolder) > 0) {
                $this->thumbnail    = $this->subfolder[0]->name.'/'.$this->subfolder[0]->thumbnail;
                $this->originaldate = $this->subfolder[0]->originaldate;
            }
        } 
    }
}

/**
 * mediaObject class handles all properties of an element (photo or video)
 */
class mediaObject
{
    public $db_id         = -1;      //!< ID of element in database tablr
    public $folder_id     = -1;      //!< ID of folder in which this element is located
    public $folder        = NULL;    //!< Reference of the folder
    public $type          = "";      //!< Element type (picture or video)
    public $tags          = array(); //!< List of tags describing this element
    public $title         = "";      //!< Element Title
    public $filesize      = -1;      //!< Filesize
    public $duration      = "";      //!< Movie only: video duration
    public $lastmod       = "";      //!< Last modification date
    public $filename      = "";      //!< Filename
    public $download_path = "";      //!< Download path (relative to $image_folder)
    public $camera        = "";      //!< Camera name
    public $focal         = -1;      //!< Focal length
    public $lens          = "";      //!< Lens model
    public $fstop         = "";      //!< Aperture value
    public $shutter       = "";      //!< Shutter value
    public $iso           = -1;      //!< ISO value
    public $originaldate  = "";      //!< Shot date
    public $width         = -1;      //!< Image width
    public $height        = -1;      //!< Image height
    public $lens_is_zoom  = -1;      //!< Zoom flag
    public $longitude     =  9999.0; //!< Longitude (in decimal format)
    public $latitude      =  9999.0; //!< Latitude (in decimal format)
    public $altitude      = -9999.0; //!< Altitude

    //! Element constructor. Only takes the containing folder reference in argument
    function __construct(mediaFolder $folder = NULL)
    {
        $this->folder = $folder;
    }

    //! Get properties from the original file
    function loadFromFile($source_filename)
    {
        global $BASE_DIR;
        global $image_folder;

        if ($this->folder != NULL) $this->download_path = $this->folder->fullname().'/'.$source_filename;
        else                       $this->download_path = $source_filename;

        $this->filename  = $source_filename;
        $source_fullname = "$BASE_DIR/$image_folder/".$this->download_path;
        $this->lastmod   = strftime('%Y/%m/%d %H:%M:%S', filemtime($source_fullname));
        $ext             = strtolower(pathinfo($source_fullname, PATHINFO_EXTENSION));
        if ($ext == 'jpg' || $ext == 'png' || $ext == 'gif' || $ext == 'bmp') {
            $this->type = 'picture';
        } else if ($ext == 'mov' || $ext == 'mpg' || $ext == 'avi') {
            $this->type = 'movie';
        } else
            throw new Exception('Unsupported file type !');

        if ($ext == 'jpg')
            $exif = exif_read_data($source_fullname);
        else
            $exif = false;

        if ($exif != false) {
            if (isset($exif['DateTimeOriginal'])) $edate = $exif['DateTimeOriginal'];
            if (empty($edate) && isset($exif['DateTime'])) $edate = $exif['DateTime'];
            if (!empty($edate)) {
                $edate = explode(':', str_replace(' ', ':', $edate));
		if (count($edate) > 5) {
                    $edate = "{$edate[0]}-{$edate[1]}-{$edate[2]} {$edate[3]}:{$edate[4]}:{$edate[5]}";
                    $edate = strftime('%Y/%m/%d %H:%M:%S', strtotime($edate));
                    $this->originaldate = $edate;
                }
            }
            if ($exif['COMPUTED']['Width'] && $exif['COMPUTED']['Height']) { 
                $this->width  = $exif['COMPUTED']['Width'];
                $this->height = $exif['COMPUTED']['Height'];
            }
            if (isset($exif['Model'])) $this->camera         = $exif['Model'];
            if (isset($exif['ISOSpeedRatings'])) {
                if (is_array($exif['ISOSpeedRatings'])) $this->iso = $exif['ISOSpeedRatings'][0];
                else                                    $this->iso = $exif['ISOSpeedRatings'];
            }
            if (exif_get_lens($exif)) $this->lens            = exif_get_lens($exif);
            $this->lens_is_zoom                              = exif_is_zoom($exif);
            if (exif_get_focal($exif)) $this->focal          = exif_get_focal($exif);
            if (exif_get_shutter($exif)) $this->shutter      = exif_get_shutter($exif);
            if (exif_get_fstop($exif)) $this->fstop          = exif_get_fstop($exif);
            if (exif_get_latitude($exif)) $this->latitude    = exif_get_latitude($exif);
            if (exif_get_longitude($exif)) $this->longitude  = exif_get_longitude($exif);
            if (isset($exif['GPSAltitude'])) $this->altitude = exif_get_float($exif['GPSAltitude']);
        }
        if ($this->originaldate == "") $this->originaldate = strftime('%Y/%m/%d %H:%M:%S', filemtime($source_fullname));
        $this->title = pathinfo($source_fullname, PATHINFO_FILENAME);
        $this->title = strtr($this->title, "_", " ");
        if ($this->type == 'picture') {
            $size = getimagesize($source_fullname, $imginfo);
            if (isset($imginfo["APP13"])) {
                $iptc = iptcparse($imginfo["APP13"]);
                if (is_array($iptc) && isset($iptc["2#120"][0]) && ($iptc["2#120"][0] != ""))
                    $this->title = $iptc["2#120"][0];
                if (is_array($iptc) && isset($iptc["2#025"][0]) && ($iptc["2#025"][0] != ""))
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

    //! Returns subtitle string: Camera - ISO, Lens (@ focal if zoom), exposure time, aperture, date, download link
    //! @param rss: This flag disable the 2nd line containing date and download link
    function getSubTitle($rss=false)
    {
        global $BASE_URL;
        global $image_folder;
        global $image_url;

        $subtitle = "";
        if ($this->camera != "") {
            $subtitle = $this->camera;
            if ($this->iso != -1)
                $subtitle .= " - ";
        }
        if ($this->iso != -1)         $subtitle .= $this->iso."ISO, ";
        if ($this->lens != "")        $subtitle .= $this->lens;
        if ($this->lens_is_zoom == 1) $subtitle .= " @ ".$this->focal."mm";
        if ($this->lens != "")        $subtitle .= ", ";
        if ($this->shutter != "")     $subtitle .= $this->shutter.", ";
        if ($this->fstop != "")       $subtitle .= $this->fstop;
        // RSS Subtitle stops here
        if ($rss == true) 
            return $subtitle;
        if ($subtitle != "")          $subtitle .= "<br />";
        $subtitle .= strftime('%d/%m/%Y %Hh%M', strtotime($this->originaldate))."<br />";
        if ($image_url == NULL)
            $subtitle .= "T&eacute;l&eacute;charger: <a href=\"$image_folder/$this->download_path\">$this->filename</a> ";
        else
            $subtitle .= "T&eacute;l&eacute;charger: <a href=\"$image_url/$this->download_path\">$this->filename</a> ";
        $esize  = "";
        if ($this->type == 'movie')
            $esize = $this->duration;
        else
            if ($this->filesize >= (1024*1024)) $esize = sprintf("(%.1fM", $this->filesize / (1024*1024));
            else                                $esize = sprintf("(%dK",  $this->filesize / 1024);
        $subtitle .= $esize.", ".$this->width."x".$this->height.")";

        return $subtitle;
    }
}

?>
