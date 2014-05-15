<?php

function update_database(mediaDB &$db = NULL, mediaFolder &$folder = NULL)
{
    // If no database given, let's open a connection
    if ($db == NULL)
        $db = new mediaDB();

    // If no folder given, assume we are in toplevel
    if ($folder == NULL) {
        $folder = new mediaFolder();
        $folder->lastupdate = $db->lastupdate;
    }

    // Check current folder
    $folder->loadFromPath($folder->name, false);

    // Check subfolders
    foreach($folder->subfolder as $subfolder) {
        if (($subfolder->db_id = $db->getFolderID($subfolder->fullname())) == -1) {
            // Folder is not in DB, scanning...
            print "-I-    Loading new folder: ".$subfolder->name."\n";
            $subfolder->loadFromPath($subfolder->name, true);
            $db->storeMediaFolder($subfolder);
        } else {
            // Folder found in DB, updating...
            update_database($db, $subfolder);
        }
    }

    // Check new elements
    foreach($folder->element as $entry) {
        if ($db->findMediaObjectID($entry) == -1) {
            // This element is not in DB, scanning...
            $db->storeMediaObject($entry, false);
        } else {
            // Element found in DB, updating 
            $db->storeMediaObject($entry, true);
        }
    }

    // Return DB object to let the caller close it
    return $db;
}

function clean_database(mediaDB &$db = NULL, $folder_id = -1)
{
    global $BASE_DIR;
    global $image_folder;

    // If no database given, let's open a connection
    if ($db == NULL)
        $db = new mediaDB();

    // Is folder still there ?
    $current_path = $db->getFolderPath($folder_id);
    if (is_dir("$BASE_DIR/$image_folder/$current_path") == true) {
        // Folder still here. Check elements
        $element_list = $db->getFolderElements($folder_id);
        foreach($element_list as $current_element) {
            $element_path = $db->getElementPath($current_element);
            if (is_file("$BASE_DIR/$image_folder/$element_path") == false) {
                // No file anymore, remove it from DB
                print "-D-   Cleaning element $current_element: $element_path\n";
                $db->removeElement($current_element);
            }
        }
        // Process subfolders
        $folder_list = $db->getSubFolders($folder_id);
        foreach($folder_list as $sub_folder) {
            clean_database($db, $sub_folder);
        }
    } else {
        // No folder anymore. Remove corresponding elements and subfolders, and folder
        print "-D-    Cleaning Folder $current_folder\n";
        $db->removeFolder($current_folder);
    }

    // Return DB object to let the caller close it
    return $db;
}

function clean_tags(mediaDB &$db)
{

    // Get elements from DB
    $element_db_list = $db->getFolderElements(-1, true);

    // Check tag table
    $results = $db->query('SELECT DISTINCT media_id FROM media_tags;');
    if ($results === FALSE) throw new Exception($this->error);
    while($row = $results->fetch_assoc()) {
        if (in_array($row['media_id'], $element_db_list) == false) {
            print "-I-    Element ".$row['media_id']." not found in elements, removing from tags table\n";
            $result = $db->query('DELETE FROM media_tags WHERE media_id='.$row['media_id'].';');
            if ($results === FALSE) throw new Exception($this->error);
        }
    }
    
}

function update_thumbnails(mediaDB &$db = NULL, $folder_id = -1)
{
    // Update folder and element thumbnails
    // Ensure correct environment variable
    putenv("MAGICK_THREAD_LIMIT=1");
    // If no database given, let's open a connection
    if ($db == NULL)
        $db = new mediaDB();

    // Process folder thumbnail
    updateFolderThumbnail($db, $folder_id);
    // Process elements for current folder
    $element_list = $db->getFolderElements($folder_id);
    foreach($element_list as $element)
        updateThumbnail($db, $element);
    // Check subfolders
    $folder_list = $db->getSubFolders($folder_id);
    foreach($folder_list as $subfolder)
        update_thumbnails($db, $subfolder);

    // Return DB object to let the caller close it
    return $db;
}

function clean_thumbnails(mediaDB &$db = NULL)
{
    global $BASE_DIR;
    global $thumb_folder;

    // Clean folder and elements thumbnails
    // If no database given, let's open a connection
    if ($db == NULL)
        $db = new mediaDB();

    // Process folder thumbnails
    $folder_db_list = $db->getSubFolders(-1, true);
    $folder_thumb_dir_list = scandir("$BASE_DIR/$thumb_folder/folders");
    $folder_thumb_list = array();
    foreach($folder_thumb_dir_list as $folder_thumb_dir) {
        if (($folder_thumb_dir[0] != '.') and (is_dir("$BASE_DIR/$thumb_folder/folders/$folder_thumb_dir") == true)) {
            // Don't look in . or .. or any hidden folder
            $folders_thumb = scandir("$BASE_DIR/$thumb_folder/folders/$folder_thumb_dir");
            // Add subfolder in front of files
            foreach($folders_thumb as $folder_thumb)
                if (is_file("$BASE_DIR/$thumb_folder/folders/$folder_thumb_dir/$folder_thumb") == true)
                   $folder_thumb_list[] = "$folder_thumb_dir/$folder_thumb";
        }
    }
    foreach($folder_thumb_list as $folder_thumb) {
        // Determine ID from thumbnail path/name
        $folder_id_hex =  $folder_thumb[0].$folder_thumb[1].$folder_thumb[3].$folder_thumb[4];
        $folder_id = base_convert($folder_id_hex, 16, 10);
        // Check presence
        if (in_array($folder_id, $folder_db_list) == false) {
            print "-I-    Folder thumbnail $folder_id was not found in database, removing\n";
            unlink("$BASE_DIR/$thumb_folder/folders/$folder_thumb");
        }
    }
    
    // Process elements thumbnails
    $element_db_list = $db->getFolderElements(-1, true);
    $element_thumb_dir_list = scandir("$BASE_DIR/$thumb_folder/elements");
    $element_thumb_list = array();
    foreach($element_thumb_dir_list as $element_thumb_dir) {
        if (($element_thumb_dir[0] != '.') and (is_dir("$BASE_DIR/$thumb_folder/elements/$element_thumb_dir") == true)) {
            // Don't look in . or .. or any hidden folder
            $elements_thumb = scandir("$BASE_DIR/$thumb_folder/elements/$element_thumb_dir");
            // Add subfolder in front of files
            foreach($elements_thumb as $element_thumb)
                if (is_file("$BASE_DIR/$thumb_folder/elements/$element_thumb_dir/$element_thumb") == true)
                   $element_thumb_list[] = "$element_thumb_dir/$element_thumb";
        }
    }
    foreach($element_thumb_list as $element_thumb) {
        // Determine ID from thumbnail path/name
        $element_id_hex =  $element_thumb[0].$element_thumb[1].$element_thumb[3].$element_thumb[4];
        $element_id = base_convert($element_id_hex, 16, 10);
        // Check presence
        if (in_array($element_id, $element_db_list) == false) {
            print "-I-    Object thumbnail $element_id was not found in database, removing\n";
            unlink("$BASE_DIR/$thumb_folder/elements/$element_thumb");
        }
    }
}

function update_resized(mediaDB &$db = NULL, $folder_id = -1)
{
    // Update folder and element resized
    // Ensure correct environment variable
    putenv("MAGICK_THREAD_LIMIT=1");
    // If no database given, let's open a connection
    if ($db == NULL)
        $db = new mediaDB();

    // Process elements for current folder
    $element_list = $db->getFolderElements($folder_id);
    foreach($element_list as $element)
        updateResized($db, $element);
    // Check subfolders
    $folder_list = $db->getSubFolders($folder_id);
    foreach($folder_list as $subfolder)
        update_resized($db, $subfolder);

    // Return DB object to let the caller close it
    return $db;
}

function clean_resized(mediaDB &$db = NULL)
{
    global $BASE_DIR;
    global $resized_folder;

    // Clean folder and elements resized
    // If no database given, let's open a connection
    if ($db == NULL)
        $db = new mediaDB();
    
    // Process elements resized
    $element_db_list = $db->getFolderElements(-1, true);
    $element_resized_dir_list = scandir("$BASE_DIR/$resized_folder");
    $element_resized_list = array();
    foreach($element_resized_dir_list as $element_resized_dir) {
        if (($element_resized_dir[0] != '.') and (is_dir("$BASE_DIR/$resized_folder/$element_resized_dir") == true)) {
            // Don't look in . or .. or any hidden folder
            $elements_resized = scandir("$BASE_DIR/$resized_folder/$element_resized_dir");
            // Add subfolder in front of files
            foreach($elements_resized as $element_resized)
                if (is_file("$BASE_DIR/$resized_folder/$element_resized_dir/$element_resized") == true)
                   $element_resized_list[] = "$element_resized_dir/$element_resized";
        }
    }
    foreach($element_resized_list as $element_resized) {
        // Determine ID from resizednail path/name
        $element_id_hex =  $element_resized[0].$element_resized[1].$element_resized[3].$element_resized[4];
        $element_id = base_convert($element_id_hex, 16, 10);
        // Check presence
        if (in_array($element_id, $element_db_list) == false) {
            print "-I-    Object resized $element_id was not found in database, removing\n";
            unlink("$BASE_DIR/$resized_folder/$element_resized");
        }
    }
}

?>
