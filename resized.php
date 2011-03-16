<?php
require_once "common_db.php";

function getResizedPath($id, mediaDB &$db = NULL)
{
    $resized = "";
    $p_id    = -1;
    $m_db    = NULL;
    if ($db == NULL)
        $m_db = new mediaDB();
    else
        $m_db = $db;
    $result  = $m_db->querySingle("SELECT folder_id, thumbnail FROM media_objects WHERE id=$id;", true);

    if ($result === false) throw new Exception($m_db->lastErrorMsg());
    $resized = $result['thumbnail'];
    $p_id    = $result['folder_id'];
    while($p_id != -1) {
        $result = $m_db->querySingle("SELECT parent_id, foldername FROM media_folders WHERE id=$p_id;", true);
        if ($result === false) throw new Exception($m_db->lastErrorMsg());
        if ($result['foldername'] != "")
            $resized = $result['foldername'].'/'.$resized;
        $p_id  = $result['parent_id'];
    }
    if ($db == NULL)
        $m_db->close();
    return $resized;
}

function updateResized($id)
{
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
    $result = $m_db->querySingle("SELECT folder_id, thumbnail, filename, type, lastmod FROM media_objects WHERE id=$id;", true);
    if ($result === false) throw new Exception($m_db->lastErrorMsg());
    if ($result['thumbnail'] == "") return false; // Should never happen
    $filename = $result['filename'];
    $resized  = $result['thumbnail'];
    $type     = $result['type'];
    $lastmod  = $result['lastmod'];
    $p_id     = $result['folder_id'];
    // Retreive full path
    while($p_id != -1) {
        $result = $m_db->querySingle("SELECT parent_id, foldername FROM media_folders WHERE id=$p_id;", true);
        if ($result === false) throw new Exception($m_db->lastErrorMsg());
        if ($result['foldername'] != "") {
            $resized  = $result['foldername'].'/'.$resized;
            $filename = $result['foldername'].'/'.$filename;
        }
        $p_id  = $result['parent_id'];
    }
    $filename = $image_folder.'/'.$filename;
    $resized  = $resized_folder.'/'.$resized;

    if ($type == 'movie') {
        $info    = pathinfo($resized);
        $resized = $info['dirname'].'/'.$info['filename'].'.flv';
    }

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
