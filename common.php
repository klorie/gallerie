<?php

/**
 * determine the file mime type
 */
function mime_type($ext) {

    // use file's extension to determine mime type
    // set defaults
    $mime_type = 'image/png';
    // mime types
    $types = array(
            'bmp'  => 'image/bmp',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'avi'  => 'video/x-flv',
            'mov'  => 'video/x-flv',
            'mpg'  => 'video/x-flv',
            'flv'  => 'video/x-flv'
            );

    if (strlen($ext) && strlen($types[$ext])) {
        $mime_type = $types[$ext];
    }
    return $mime_type;
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

function parentFolder($path) {
    $path_hier = explode('/', $path);
    if (count($path_hier) > 1)
        return implode('/', array_pop($path_hier));
    else
        return "";
}

function getProcessingTime($starttime)
{
    $mtime = explode(' ', microtime());
    $totaltime = ($mtime[0] + $mtime[1] - $starttime);
    if ($totaltime < 60) {
        return round($totaltime, 1)."s";
    } else if ($totaltime < 3600) {
        $totaltime = $totaltime / 60;
        return round($totaltime, 1)."min";
    } else {
        $totaltime = $totaltime / 3600;
        return round($totaltime, 2)."h";
    }
}

function baseDir()
{
    return dirname($_SERVER['SCRIPT_FILENAME']);
}

function baseURL()
{
    return "http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']);
}
?>
