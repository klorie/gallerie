<?php

function getResizedPath($id, mediaDB &$db = NULL)
{
    $resized = "";
    $p_id    = -1;
    $m_db    = NULL;
    if ($db == NULL)
        $m_db = new mediaDB();
    else
        $m_db = $db;
    $result  = $m_db->query("SELECT folder_id, resized FROM media_objects WHERE id=$id;");
    if ($result === false) throw new Exception($m_db->error); else $row = $result->fetch_assoc();
    $result->free();
    $resized = $row['resized'];
    $p_id    = $row['folder_id'];

    while($p_id != -1) {
        $result = $m_db->query("SELECT parent_id, foldername FROM media_folders WHERE id=$p_id;");
        if ($result === false) throw new Exception($m_db->error); else $row = $result->fetch_assoc();
        $result->free();
        if ($row['foldername'] != "")
            $resized = $row['foldername'].'/'.$resized;
        $p_id  = $row['parent_id'];
    }
    if ($db == NULL)
        $m_db->close();
    return $resized;
}

function updateResized($id)
{
    global $BASE_DIR;
    global $resized_size;
    global $resized_folder;
    global $image_folder;

    $filename  = "";
    $resized   = "";
    $type      = "";
    $lastmod   = "";
    $p_id      = -1;

    $m_db = new mediaDB();

    set_time_limit(30); // Set time limit to avoid timeout

    // Get Object info
    $result = $m_db->query("SELECT folder_id, resized, filename, type, lastmod FROM media_objects WHERE id=$id;");
    if ($result === false) throw new Exception($m_db->error); else $row = $result->fetch_assoc();
    $result->free();
    if ($row['resized'] == "") return false; // Should never happen
    $filename = $row['filename'];
    $resized  = $row['resized'];
    $type     = $row['type'];
    $lastmod  = $row['lastmod'];
    $p_id     = $row['folder_id'];
    // Retreive full path
    while($p_id != -1) {
        $result = $m_db->query("SELECT parent_id, foldername FROM media_folders WHERE id=$p_id;");
        if ($result === false) throw new Exception($m_db->error); else $row = $result->fetch_assoc();
        $result->free();
        if ($row['foldername'] != "") {
            $resized  = $row['foldername'].'/'.$resized;
            $filename = $row['foldername'].'/'.$filename;
        }
        $p_id  = $row['parent_id'];
    }
    $filename = "$BASE_DIR/$image_folder/".$filename;
    $resized  = "$BASE_DIR/$resized_folder/".$resized;

    if (file_exists($resized) && (filemtime($resized) > filemtime($filename))) return false; // No need to update

    if ($type == 'picture') {
        // Create picture resized
        // load image and get image size
        if (!extension_loaded('imagick')) die("-E-   php_imagick extension is required !\n");
        if (getenv('MAGICK_THREAD_LIMIT') == "") die("-E-   This script requires the MAGICK_THREAD_LIMIT=1 line to be added in /etc/environment !\n");
        $img    = new Imagick();
        $img->ReadImage($filename);
        $width  = $img->GetImageWidth();
        $height = $img->GetImageHeight();
        // Compute resized size
        if ($width >= $height) {
            $orientation = 0;
            $ratio = $width / $height;
        } else {
            $orientation = 1;
            $ratio = $height / $width;
        }

        $new_width = $resized_size;
        if ($orientation == 0) {
            $new_height = $new_width / $ratio;
        } else {
            $new_height = $new_width;
            $new_width  = $new_height / $ratio;
        }
        $img->thumbnailImage($new_width, $new_height);
        $img->setImageFormat("jpeg");
        $img->setCompressionQuality(65);
        $img->setImageFilename($resized);
        $img->WriteImage();
        $img->clear();
        $img->destroy();
    } else {
        // Create video thumbnail
        exec("mencoder \"$filename\" -o \"$resized\" -quiet -of lavf -oac mp3lame -lameopts abr:br=64:mode=3 -ovc lavc -lavcopts vcodec=flv:vbitrate=1600:mbd=2:mv0:trell:v4mv:cbp:last_pred=4 -ofps 15 -srate 44100");
    }

    $m_db->close();
    return true;
}

?>
