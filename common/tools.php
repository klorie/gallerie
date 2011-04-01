<?php

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

?>
