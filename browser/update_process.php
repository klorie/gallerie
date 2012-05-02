<?php
// Start PHP code
require_once "../include.php";
require_once "display.php";

global $BASE_DIR;
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
    exec("rm -rf $BASE_DIR/$thumb_folder");
    @mkdir("$BASE_DIR/$thumb_folder");
    @session_start();
    $_SESSION['progress'] = 50;
    $_SESSION['status']   = "Removing all resized";
    session_commit();
    exec("rm -rf $BASE_DIR/$resized_folder");
    @mkdir("$BASE_DIR/$resized_folder");
    @session_start();
    $_SESSION['progress'] = 100;
    $_SESSION['status']   = "Thumbnails and resized deleted.";
    session_commit();
} else if ($task == 'update_db') {
    // Update DB according to folder/file structure
    // Remove database to ensure clean update
    @session_start();
    $_SESSION['progress'] = 1;
    $_SESSION['status']   = "Parsing gallery folder";
    session_commit();
    $gallery = new mediaFolder();
    $gallery->loadFromPath();
    @session_start();
    $_SESSION['nbfolders'] = 0;
    $_SESSION['totalfolders'] = $gallery->getSubFolderCount();
    $_SESSION['progress'] = 50;
    $_SESSION['status']   = "Storing information into database";
    session_commit();
    $gallery_db = new mediaDB();
    $result     = $gallery_db->query('DROP TABLE IF EXISTS media_folders, media_objects, media_tags;');
    if ($result === FALSE) throw new Exception($gallery_db->error); 
    $gallery_db->init_database();
    $gallery_db->storeMediaFolder($gallery);
    $result        = $gallery_db->query("SELECT COUNT(*) FROM media_objects;");
    $row           = $result->fetch_row();
    $element_count = $row[0];
    $result        = $gallery_db->query("SELECT COUNT(*) FROM media_folders;");
    $row           = $result->fetch_row();
    $folder_count  = $row[0];
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
    $result         = $gallery_db->query("SELECT COUNT(*) FROM media_objects;");
    $row            = $result->fetch_row();
    $process_count  = $row[0];
    $result         = $gallery_db->query("SELECT COUNT(*) FROM media_folders;");
    $row            = $result->fetch_row();
    $process_count += $row[0];
    @session_start();
    $_SESSION['progress'] = 0;
    $_SESSION['status']   = "Processing folders";
    session_commit();

    $folder_list = array();
    $folder_list_db = $gallery_db->query("SELECT id FROM media_folders;");
    if($folder_list_db === false) throw new Exception ($gallery_db->error);
    while($folder = $folder_list_db->fetch_assoc()) {
        $folder_list[] = $folder['id'];
    }
    $folder_list_db->free();
    $folder_idx = 0;
    foreach($folder_list as $folder_id) {
        $progress = floor($folder_idx/$process_count*100);
        @session_start();
        $_SESSION['progress'] = $progress;
        $_SESSION['status']   = "Processing folders ($progress%)...";
        session_commit();

        $folder_path = $gallery_db->getFolderPath($folder_id);
        if (!file_exists("$BASE_DIR/$thumb_folder/$folder_path")) { 
            mkdir("$BASE_DIR/$thumb_folder/$folder_path", 0777, true);
        }
        if (!file_exists("$BASE_DIR/$resized_folder/$folder_path")) {
            mkdir("$BASE_DIR/$resized_folder/$folder_path", 0777, true); 
        }
        updateFolderThumbnail($folder_id);
        $folder_idx++;
    }
    $element_idx   = $folder_idx;
    $element_list  = $gallery_db->query("SELECT id FROM media_objects;");
    if($element_list === false) throw new Exception ($gallery_db->error);
    while($element = $element_list->fetch_assoc()) {
        $progress = floor($element_idx/$process_count*100);
        @session_start();
        $_SESSION['progress'] = $progress;
        $_SESSION['status']   = "Processing elements ($progress%)...";
        session_commit();
        updateThumbnail($element['id']);
        updateResized($element['id']);
        $element_idx++;
    }
    $element_list->free();
    @session_start();
    $_SESSION['progress'] = 100;
    $_SESSION['status']   = "Processing of thumbnails and resized done.";
    session_commit();
    $gallery_db->close();
} else if ($task == 'clean_thumb') {
    // Clean thumbnails and resized
    $gallery_db = new mediaDB();
    $result        = $gallery_db->query("SELECT COUNT(*) FROM media_folders;");
    $row           = $result->fetch_row();
    $process_count = $row[0];
    @session_start();
    $_SESSION['progress'] = 0;
    $_SESSION['status']   = "Starting cleaning thumbnails and resized";
    session_commit();

    $folder_list = array();
    $folder_list_db = $gallery_db->query("SELECT id FROM media_folders;");
    if($folder_list_db === false) throw new Exception ($gallery_db->error);
    while($folder = $folder_list_db->fetch_assoc()) {
        $folder_list[] = $folder['id'];
    }
    $folder_list_db->free();

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
        if ($thumbnail_list_db === false) throw new Exception($gallery_db->error);
        while($thumbnail_db = $thumbnail_list_db->fetch_assoc()) {
            $thumbnail_list[] = getThumbnailPath($thumbnail_db['id']);
        }
        $thumbnail_list_db->free();

        // open the thumbnail directory
        $dir = opendir("$BASE_DIR/$thumb_folder/$folder_path");
        if ($dir === false) throw new Exception("-E-   Failed to open $BASE_DIR/$thumb_folder/$folder_path !");

        // loop through it
        while (false !== ($fname = readdir($dir))) {
            if (is_file("$BASE_DIR/$thumb_folder/$folder_path/$fname")) {
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
                    unlink("$BASE_DIR/$thumb_folder/$folder_path/$fname");
                }
            }
        }
        closedir($dir);

        // build folder resized list
        $resized_list = array();
        $resized_list_db = $gallery_db->query("SELECT id FROM media_objects WHERE folder_id=$folder_id;");
        if ($resized_list_db === false) throw new Exception($gallery_db->error);
        while($resized_db = $resized_list_db->fetch_assoc()) {
            $resized_list[] = getResizedPath($resized_db['id']);
        }
        $resized_list_db->free();

        // open the resized directory
        $dir = opendir("$BASE_DIR/$resized_folder/$folder_path");
        if ($dir === false) throw new Exception("-E-   Failed to open $BASE_DIR/$resized_folder/$folder_path !");

        // loop through it
        while (false !== ($fname = readdir($dir))) {
            if (is_file("$BASE_DIR/$resized_folder/$folder_path/$fname")) {
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
                    unlink("$BASE_DIR/$resized_folder/$folder_path/$fname");
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
} else {
    throw new Exception ("Wrong task selected ($task) !");
}

?>
