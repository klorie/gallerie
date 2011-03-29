<?php
// Start PHP code
require_once "thumbnail.php";
require_once "resized.php";
require_once "common_browser.php";

global $thumb_folder;
global $resized_folder;
global $image_folder;

$task = "";

if (isset($_REQUEST['task'])) {
    $task = $_REQUEST['task'];
} else {
    throw new Exception('-E-   Task was not specified. aborting');
}

set_time_limit(9999);

if ($task == 'clear_thumb') {
    // Clean all unneeded thumbnails
    @session_start();
    $_SESSION['progress'] = 1;
    $_SESSION['status']   = "Removing all thumbnails";
    session_commit();
    exec("rm -rf ".baseDir()."/$thumb_folder");
    @mkdir(baseDir()."/$thumb_folder");
    @session_start();
    $_SESSION['progress'] = 50;
    $_SESSION['status']   = "Removing all resized";
    session_commit();
    exec("rm -rf ".baseDir()."/$resized_folder");
    @mkdir(baseDir()."/$resized_folder");
    @session_start();
    $_SESSION['progress'] = 100;
    $_SESSION['status']   = "Thumbnails and resized deleted.";
    session_commit();
} else if ($task == 'update_db') {
    // Update DB according to folder/file structure
    // Remove database to ensure clean update
    @unlink(baseDir()."/gallery.db");
    @session_start();
    $_SESSION['progress'] = 1;
    $_SESSION['status']   = "Parsing gallery folder";
    session_commit();
    $gallery = new mediaFolder();
    $gallery->loadFromPath();
    @session_start();
    $_SESSION['nbfolders'] = 0;
    $_SESSION['totalfolders'] = $gallery->getSubFolderCount();
    $_SESSION['progress'] = 10;
    $_SESSION['status']   = "Storing information into database";
    session_commit();
    $gallery_db = new mediaDB();
    $gallery_db->storeMediaFolder($gallery);
    $element_count = $gallery_db->querySingle("SELECT COUNT(*) FROM media_objects;");
    $folder_count  = $gallery_db->querySingle("SELECT COUNT(*) FROM media_folders;");
    @session_start();
    $_SESSION['progress'] = 100;
    $_SESSION['status']   = "Processed $element_count elements in $folder_count folders";
    session_commit();
    $gallery_db->close();
} else if ($task == 'update_thumb') {
    // Update folder and element thumbnails
    // Ensure correct environment variable
    putenv("MAGICK_THREAD_LIMIT=1");
    $gallery_db = new mediaDB();
    $process_count  = $gallery_db->querySingle("SELECT COUNT(*) FROM media_objects;");
    $process_count += $gallery_db->querySingle("SELECT COUNT(*) FROM media_folders;");
    @session_start();
    $_SESSION['progress'] = 0;
    $_SESSION['status']   = "Processing folders";
    session_commit();

    $folder_list = array();
    $folder_list_db = $gallery_db->query("SELECT id FROM media_folders;");
    if($folder_list_db === false) throw new Exception ($gallery_db->lastErrorMsg());
    while($folder = $folder_list_db->fetchArray(SQLITE3_ASSOC)) {
        $folder_list[] = $folder['id'];
    }
    $folder_list_db->finalize();
    $folder_idx = 0;
    foreach($folder_list as $folder_id) {
        $progress = floor($folder_idx/$process_count*100);
        @session_start();
        $_SESSION['progress'] = $progress;
        $_SESSION['status']   = "Processing folders ($progress%)...";
        session_commit();

        $folder_path = $gallery_db->getFolderPath($folder_id);
        if (!file_exists(baseDir()."/$thumb_folder/$folder_path")) { 
            mkdir(baseDir()."/$thumb_folder/$folder_path", 0777, true);
        }
        if (!file_exists(baseDir()."/$resized_folder/$folder_path")) {
            mkdir(baseDir()."/$resized_folder/$folder_path", 0777, true); 
        }
        updateFolderThumbnail($folder_id);
        $folder_idx++;
    }
    $element_idx   = $folder_idx;
    $element_list  = $gallery_db->query("SELECT id FROM media_objects;");
    if($element_list === false) throw new Exception ($gallery_db->lastErrorMsg());
    while($element = $element_list->fetchArray()) {
        $progress = floor($element_idx/$process_count*100);
        @session_start();
        $_SESSION['progress'] = $progress;
        $_SESSION['status']   = "Processing elements ($progress%)...";
        session_commit();
        updateThumbnail($element['id']);
        updateResized($element['id']);
        $element_idx++;
    }
    @session_start();
    $_SESSION['progress'] = 100;
    $_SESSION['status']   = "Processing of thumbnails and resized done.";
    session_commit();
    $gallery_db->close();
} else if ($task == 'clean_thumb') {
    // Clean thumbnails and resized
    $gallery_db = new mediaDB();
    $process_count = $gallery_db->querySingle("SELECT COUNT(*) FROM media_folders;");
    @session_start();
    $_SESSION['progress'] = 0;
    $_SESSION['status']   = "Starting cleaning thumbnails and resized";
    session_commit();

    $folder_list = array();
    $folder_list_db = $gallery_db->query("SELECT id FROM media_folders;");
    if($folder_list_db === false) throw new Exception ($gallery_db->lastErrorMsg());
    while($folder = $folder_list_db->fetchArray(SQLITE3_ASSOC)) {
        $folder_list[] = $folder['id'];
    }
    $folder_list_db->finalize();

    $folder_idx = 0;
    foreach($folder_list as $folder_id) {
        $folder_path = $gallery_db->getFolderPath($folder_id);
        @session_start();
        $_SESSION['progress'] = floor($folder_idx/$process_count*100);
        $_SESSION['status']   = "Cleaning $folder_path...";
        session_commit();

        // build folder thumbnail list
        $thumbnail_list = array();
        $thumbnail_list_db = $gallery_db->query("SELECT id FROM media_objects WHERE folder_id=$folder_id;");
        if ($thumbnail_list_db === false) throw new Exception($gallery_db->lastErrorMsg());
        while($thumbnail_db = $thumbnail_list_db->fetchArray(SQLITE3_ASSOC)) {
            $thumbnail_list[] = getThumbnailPath($thumbnail_db['id']);
        }
        $thumbnail_list_db->finalize();

        // open the thumbnail directory
        $dir = opendir(baseDir()."/$thumb_folder/$folder_path");
        if ($dir === false) throw new Exception("-E-   Failed to open ".baseDir()."/$thumb_folder/$folder_path !");

        // loop through it
        while (false !== ($fname = readdir($dir))) {
            if (is_file(baseDir()."/$thumb_folder/$folder_path/$fname")) {
                if ($fname == $folder_thumbname) continue;
                // Check if file exist
                $found = false;
                foreach($thumbnail_list as $thumbnail_path) {
                    if (stristr($thumbnail_path, "$folder_path/$fname") !== false)
                        $found = true;
                    else if (($folder_path == "") && (stristr($thumbnail_path, $fname) !== false))
                        $found = true;
                }
                if ($found == false) {
                    // Image not found, removing thumbnail
                    unlink(baseDir()."/$thumb_folder/$folder_path/$fname");
                }
            }
        }
        closedir($dir);

        // build folder resized list
        $resized_list = array();
        $resized_list_db = $gallery_db->query("SELECT id FROM media_objects WHERE folder_id=$folder_id;");
        if ($resized_list_db === false) throw new Exception($gallery_db->lastErrorMsg());
        while($resized_db = $resized_list_db->fetchArray(SQLITE3_ASSOC)) {
            $resized_list[] = getResizedPath($resized_db['id']);
        }
        $resized_list_db->finalize();

        // open the resized directory
        $dir = opendir(baseDir()."/$resized_folder/$folder_path");
        if ($dir === false) throw new Exception("-E-   Failed to open ".baseDir()."/$resized_folder/$folder_path !");

        // loop through it
        while (false !== ($fname = readdir($dir))) {
            if (is_file(baseDir()."/$resized_folder/$folder_path/$fname")) {
                // Check if file exist
                $found = false;
                foreach($resized_list as $resized_path) {
                    $fname_noext  = substr($fname, 0, strlen($fname) - 3);
                    if (stristr($resized_path, "$folder_path/$fname_noext") !== false)
                        $found = true;
                    else if (($folder_path == "") && (stristr($resized_path, $fname_noext) !== false))
                        $found = true;
                }
                if ($found == false) {
                    // Image not found, removing resized
                    unlink(baseDir()."/$resized_folder/$folder_path/$fname");
                }
            }
        }
        closedir($dir);
        $folder_idx++;
    }
    @session_start();
    $_SESSION['progress'] = 100;
    $_SESSION['status']   = "Thumbnail and resized cleaned";
    session_commit();
    $gallery_db->close();
} else if ($task == 'update_theme') {
    // Ensure correct environment variable
    putenv("MAGICK_THREAD_LIMIT=1");

    $gallery_db = new mediaDB();

    @session_start();
    $_SESSION['progress'] = 0;
    $_SESSION['status']   = "Updating top folder thumbnails";
    session_commit();
    updateTopFolderMenuThumbnail();
    @session_start();
    $_SESSION['progress'] = 50;
    $_SESSION['status']   = "Updating top folder stylesheet";
    session_commit();
    generateTopFolderStylesheet($gallery_db);
    @session_start();
    $_SESSION['progress'] = 100;
    $_SESSION['status']   = "Theme update complete.";
    session_commit();
    $gallery_db->close();
} else if ($task == 'clear_cache') {
    exec("rm -rf ".baseDir()."/$cache_folder");
    @mkdir(baseDir()."/$cache_folder");   
} else {
    throw new Exception ("Wrong task selected ($task) !");
}

?>
