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
?>
