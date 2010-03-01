<?php
require_once "config.php";
require_once "common.php";
/*
    Parameters
    ---------
    w: width
    h: height
    q: quality (default is 75 and max is 100)
    
    HTML example: <img src="/resize.php?src=/toto.jpg&w=150&h=0" />
*/

// check to see if GD function exist
if(!function_exists('imagecreatetruecolor')) {
    displayError('GD Library Error: imagecreatetruecolor does not exist - please contact your webhost and ask them to install the GD library');
}

define ('CACHE_CLEAR', 5);        // maximum number of files to delete on each cache clear

// sort out image source
$src = get_request("src", "");
if($src == '' || strlen($src) <= 3) {
    displayError ('no image specified');
}

// clean params before use
$src = cleanSource($src);
// last modified time (for caching)
$lastModified = filemtime($src);

// get properties
$new_width  = preg_replace("/[^0-9]+/", "", get_request("w",  0));
$new_height = preg_replace("/[^0-9]+/", "", get_request("h",  0));
$quality    = preg_replace("/[^0-9]+/", "", get_request("q", 75));

if ($new_width == 0 && $new_height == 0) {
    $new_width = 100;
    $new_height = 100;
}

// set path to cache directory (default is ./cache)
// this can be changed to a different location
$cache_dir = './cache/resized';

// get mime type of src
$mime_type = mime_type($src);

// check to see if this image is in the cache already
check_cache ($cache_dir, $mime_type);

// if not in cache then clear some space and generate a new file
cleanCache();

ini_set('memory_limit', "50M");

// make sure that the src is gif/jpg/png
if(!valid_src_mime_type($mime_type)) {
    displayError("Invalid src mime type: " .$mime_type);
}

if(strlen($src) && file_exists($src)) {
    // open the existing image
    $image = open_image($mime_type, $src);
    if($image === false) {
        displayError('Unable to open image : ' . $src);
    }

    // Get original width and height
    $width  = imagesx($image);
    $height = imagesy($image);
    if ($width >= $height) {
        $orientation = 0;
        $ratio = $width / $height;
    } else {
        $orientation = 1;
        $ratio = $height / $width;
    }
    
    // If both dimension are not provided, keep the ratio
    if ($new_width && !$new_height)
        if ($orientation == 0) {
            $new_height = $new_width / $ratio;
        } else {
            $new_height = $new_width;
            $new_width  = $new_height / $ratio;
        }
    elseif ($new_height && !$new_width)
        if ($orientation == 0) {
            $new_width  = $new_height * $ratio;
        } else {
            $new_width  = $new_height;
            $new_height = $new_width * $ratio;
        }
    elseif (!$new_width && !$new_height) {
        $new_width  = $width;
        $new_height = $height;
    }
            
    // create a new true color image
    $canvas = imagecreatetruecolor( $new_width, $new_height );

    // copy and resize part of an image with resampling
    imagecopyresampled( $canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
    
    // output image to browser based on mime type
    show_image($mime_type, $canvas, $cache_dir);
    
    // remove image from memory
    imagedestroy($canvas);
} else {
    if(strlen($src)) {
        displayError("image ".$src." not found");
    } else {
        displayError("no source specified");
    }
}

/**
 * 
 */
function show_image($mime_type, $image_resized, $cache_dir) {

    global $quality;

    // check to see if we can write to the cache directory
    $is_writable = 0;
    $cache_file_name = $cache_dir . '/' . get_cache_file();

    if (touch($cache_file_name)) {
        // give 666 permissions so that the developer 
        // can overwrite web server user
        chmod ($cache_file_name, 0666);
        $is_writable = 1;
    } else {
        $cache_file_name = NULL;
        header ('Content-type: ' . $mime_type);
    }

    imagejpeg($image_resized, $cache_file_name, $quality);

    if ($is_writable) {
        show_cache_file ($cache_dir, $mime_type);
    }
    imagedestroy ($image_resized);
    displayError ("error showing image");
}

/**
 * 
 */
function get_request( $property, $default = 0 ) {
    if( isset($_REQUEST[$property]) ) {
        return $_REQUEST[$property];
    } else {
        return $default;
    }
}

/**
 * 
 */
function open_image($mime_type, $src) {
    $mime_type = strtolower($mime_type);
	
    if (stristr ($mime_type, 'gif')) {
        $image = imagecreatefromgif($src);
    } elseif (stristr($mime_type, 'jpeg')) {
        @ini_set ('gd.jpeg_ignore_warning', 1);
        $image = imagecreatefromjpeg($src);
    } elseif (stristr ($mime_type, 'png')) {
        $image = imagecreatefrompng($src);
    }
    return $image;
}

/**
 * clean out old files from the cache
 * you can change the number of files to store and to delete per loop in the defines at the top of the code
 */
function cleanCache() {
    global $resize_cache_day, $resize_cache_size;

    $files = glob("cache/resized/*", GLOB_BRACE);
    
    if (count($files) > 0) {
        $cache_timeout = time() - (24 * 60 * 60 * $resize_cache_day);
        
        usort($files, 'filemtime_compare');
        $i = 0;
        
        if (count($files) > $resize_cache_size) {
            foreach ($files as $file) {
                $i++;
                if (($i >= CACHE_CLEAR) || (@filemtime($file) > $cache_timeout)) return;
		if (file_exists($file)) {
			unlink($file);
		}
            }
        }
    }
}


/**
 * compare the file time of two files
 */
function filemtime_compare($a, $b) {
    return filemtime($a) - filemtime($b);
}


/**
 * 
 */
function check_cache ($cache_dir, $mime_type) {

    // make sure cache dir exists
    if (!file_exists($cache_dir)) {
        // give 777 permissions so that developer can overwrite
        // files created by web server user
        mkdir($cache_dir);
        chmod($cache_dir, 0777);
    }

    show_cache_file ($cache_dir, $mime_type);

}


/**
 * 
 */
function show_cache_file ($cache_dir, $mime_type) {

    $cache_file = $cache_dir . '/' . get_cache_file();

    if (file_exists($cache_file)) {
        
        $gmdate_mod = gmdate("D, d M Y H:i:s", filemtime($cache_file));
        
        if(! strstr($gmdate_mod, "GMT")) {
            $gmdate_mod .= " GMT";
        }
        
        if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
        
            // check for updates
            $if_modified_since = preg_replace ("/;.*$/", "", $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
            
            if ($if_modified_since == $gmdate_mod) {
                header("HTTP/1.1 304 Not Modified");
                die();
            }

        }
        
        $fileSize = filesize ($cache_file);
        
        // send headers then display image
        header ('Content-Type: ' . $mime_type);
        header ('Accept-Ranges: bytes');
        header ('Last-Modified: ' . $gmdate_mod);
        header ('Content-Length: ' . $fileSize);
        header ('Cache-Control: max-age=9999, must-revalidate');
        header ('Expires: ' . $gmdate_mod);
        
        readfile ($cache_file);
        
        die();

    }
    
}


/**
 * 
 */
function get_cache_file() {

    global $lastModified;
    static $cache_file;
    
    if (!$cache_file) {
        $cachename = $_SERVER['QUERY_STRING'].$lastModified;
        $cache_file = md5($cachename) . '.jpg';
    }
    
    return $cache_file;

}


/**
 * check to if the url is valid or not
 */
function valid_extension ($ext) {

    if (preg_match("/jpg|jpeg|png|gif/i", $ext)) {
        return TRUE;
    } else {
        return FALSE;
    }
    
}

/**
 * tidy up the image source url
 */
function cleanSource($src) {

    // remove slash from start of string
    if (strpos($src, '/') === 0) {
        $src = substr ($src, -(strlen($src) - 1));
    }

    // don't allow users the ability to use '../' 
    // in order to gain access to files below document root
    $src = preg_replace("/\.\.+\//", "", $src);
    
    return $src;

}

/**
 * generic error message
 */
function displayError($errorString = '') {

    header('HTTP/1.1 400 Bad Request');
    die($errorString);
    
}
?>
