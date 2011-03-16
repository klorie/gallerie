<?php

// Start PHP code
require_once "thumbnail.php";
require_once "resized.php";

global $thumb_folder;
global $resized_folder;
global $image_folder;

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

// This script may run for a loooong time...
set_time_limit(9999);

$clean = false;
if ((isset($_REQUEST['clean']))) {
    $clean = $_REQUEST['clean'];
}
if (isset($argv[1])) $clean = ($argv[1] == "--clean");
// Free does not allow directories to be removed, only files.
//
if ($clean) {
    // Clean all unneeded thumbnails
    echo "Removing all thumbnails\n";
    exec("rm -rf $thumb_folder");
    mkdir("$thumb_folder");
    echo "Removing all resized\n";
    exec("rm -rf $resized_folder");
    mkdir("$resized_folder");
}

// Remove database to ensure clean update
unlink("gallery.db");

echo "Parsing gallery folder...";
$gallery = new mediaFolder();
$gallery->loadFromPath();
echo "[ Done ]\n";
$mtime = explode(' ', microtime());
$totaltime = ($mtime[0] + $mtime[1] - $starttime) / 60;
if ($totaltime < 60) {
    echo "-D- Parsing done in $totaltime min\n";
} else {
    $totaltime = $totaltime / 60;
    echo "-D- Parsing done in $totaltime hours\n";
}
echo "Storing information into database...";
$gallery_db = new mediaDB();
$gallery_db->storeMediaFolder($gallery);
echo "[ Done ]\n";
$mtime = explode(' ', microtime());
$totaltime = ($mtime[0] + $mtime[1] - $starttime) / 60;
if ($totaltime < 60) {
    echo "-D- Storing done in $totaltime min\n";
} else {
    $totaltime = $totaltime / 60;
    echo "-D- Storing done in $totaltime hours\n";
}

$element_count = $gallery_db->querySingle("SELECT COUNT(*) FROM media_objects;");
$folder_count  = $gallery_db->querySingle("SELECT COUNT(*) FROM media_folders;");
echo "Found $element_count elements and $folder_count folders to process\n";

// Clean and create folders
echo "Processing folders...\n";
$folder_list = $gallery_db->query("SELECT id FROM media_folders;");
if($folder_list === false) throw new Exception ($gallery_db->lastErrorMsg());
while($folder = $folder_list->fetchArray()) {
    $folder_path = $gallery_db->getFolderPath($folder['id']);
    echo "-D- processing $folder_path \n";
    if (!file_exists("$thumb_folder/$folder_path")) { 
        mkdir("$thumb_folder/$folder_path", 0777, true);
    }
    if (!file_exists("$resized_folder/$folder_path")) {
        mkdir("$resized_folder/$folder_path", 0777, true); 
    }
    updateFolderThumbnail($folder['id']);

    // open the thumbnail directory
    $dir = opendir("$thumb_folder/$folder_path");
    if ($dir === false) echo "-E-   Failed to open $thumb_folder/$folder_path !\n";

    // loop through it
    while (false !== ($fname = readdir($dir))) {
        if (is_file("$thumb_folder/$folder_path/$fname")) {
            if ($fname == $folder_thumbname) continue;
            // Check if file exist
            $found = false;
            $element_list = $gallery_db->query("SELECT id FROM media_objects WHERE folder_id=".$folder['id']);
            if ($element_list === false) throw new Exception ($gallery_db->lastErrorMsg());
            while($element = $element_list->fetchArray()) {
                $thumbnail_path = getThumbnailPath($element['id'], $gallery_db);
                if (stristr($thumbnail_path, "$folder_path/$fname") !== false)
                    $found = true;
                else if (($folder_path == "") && (stristr($thumbnail_path, "$fname") !== false))
                    $found = true;
            }
            if ($found == false) {
                // Image not found, removing thumbnail
                echo "$fname element not found, removing thumbnail\n";
                unlink("$thumb_folder/$folder_path/$fname");
            }
        }
    }
    closedir($dir);

    // open the resized directory
    $dir = opendir("$resized_folder/$folder_path");

    // loop through it
    while (false !== ($fname = readdir($dir))) {
        if (is_file("$resized_folder/$folder_path/$fname")) {
            // Check if file exist
            $found = false;
            $element_list = $gallery_db->query("SELECT id FROM media_objects WHERE folder_id=".$folder['id']);
            if ($element_list === false) throw new Exception ($gallery_db->lastErrorMsg());
            while($element = $element_list->fetchArray()) {
                $fname_noext  = substr($fname, 0, strlen($fname) - 3);
                $resized_path = getResizedPath($element['id'], $gallery_db);
                if (stristr($resized_path, "$folder_path/$fname_noext") !== false)
                    $found = true;
                else if (($folder_path == "") && (stristr($resized_path, "$fname_noext") !== false))
                    $found = true;
            }
            if ($found == false) {
                // Image not found, removing resized
                echo "$fname element not found, removing resized\n";
                unlink("$resized_folder/$folder_path/$fname");
            }
        }
    }
    closedir($dir);
}
echo "Folder processing done.\n";

echo "Processing elements...";
$element_list = $gallery_db->query("SELECT id FROM media_objects;");
if($element_list === false) throw new Exception ($gallery_db->lastErrorMsg());
while($element = $element_list->fetchArray()) {
    updateThumbnail($element['id']);
    updateResized($element['id']);
}
echo "[ Done ]\n";

$mtime = explode(' ', microtime());
$totaltime = ($mtime[0] + $mtime[1] - $starttime) / 60;
if ($totaltime < 60) {
    echo "Processing done in $totaltime min\n";
} else {
    $totaltime = $totaltime / 60;
    echo "Processing done in $totaltime hours\n";
}

?>
