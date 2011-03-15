<?php
require_once "common_db.php";

function getObjectThumbnailPath($id)
{
    $thumb  = "";
    $p_id   = -1;
    $m_db   = new mediaDB();
    $result = $m_db->querySingle("SELECT folder_id, thumbnail FROM media_objects WHERE id=$id;", true);

    if ($result === false) throw new Exception($m_db->lastErrorMsg());
    $thumb  = $result['thumbnail'];
    $p_id   = $result['folder_id'];
    $folder = $m_db->getMediaFolderPath($p_id);
    if ($folder != "")
        $thumb = $folder.'/'.$thumb;
    return $thumb;
}

function getFolderThumbnailPath($id)
{
    $thumb  = "";
    $p_id   = -1;
    $m_db   = new mediaDB();
    $result = $m_db->querySingle("SELECT thumbnail FROM media_folders WHERE id=$id;", true);

    if ($result === false) throw new Exception($m_db->lastErrorMsg());
    $thumb  = $result['thumbnail'];
    $folder = $m_db->getMediaFolderPath($p_id);
    if ($folder != "")
        $thumb = $folder.'/'.$thumb;
    return $thumb;
}

function updateObjectThumbnail($id)
{
    global $thumb_size;
    global $thumb_folder;
    global $image_folder;

    $filename  = "";
    $thumbnail = "";
    $type      = "";
    $lastmod   = "";
    $p_id      = -1;

    $m_db = new mediaDB();

    set_time_limit(30); // Set time limit to avoid timeout

    // Get Object info
    $result = $m_db->querySingle("SELECT folder_id, thumbnail, filename, type, lastmod FROM media_objects WHERE id=$id;", true);
    if ($result === false) throw new Exception($m_db->lastErrorMsg());
    if ($result['thumbnail'] == "") return false; // Should never happen
    $filename  = $result['filename'];
    $thumbnail = $result['thumbnail'];
    $type      = $result['type'];
    $lastmod   = $result['lastmod'];
    $p_id      = $result['folder_id'];
    // Retreive full path
    $filename  = $image_folder.'/'.$m_db->getMediaFolderPath($p_id).'/'.$filename;
    $thumbnail = $thumb_folder.'/'.$m_db->getMediaFolderPath($p_id).'/'.$thumbnail;

    if (file_exists($thumbnail) && (filemtime($thumbnail) > strftime($lastmod))) return false; // No need to update

    if ($type == 'picture') {
        // Create picture thumbnail
        // load image and get image size
        if (!extension_loaded('imagick')) die("-E-   php_imagick extension is required !\n");
        if (getenv('MAGICK_THREAD_LIMIT') == "") die("-E-   This script requires the MAGICK_THREAD_LIMIT=1 line to be added in /etc/environment !\n");
        $img    = new Imagick();
        $img->ReadImage($filename);
        $width  = $img->GetImageWidth();
        $height = $img->GetImageHeight();
        // calculate thumbnail size
        if ($width >= $height) {
            $new_width  = $thumb_size;
            $new_height = $thumb_size * 0.75;
        } else {
            $new_height = $thumb_size;
            $new_width  = $thumb_size * 0.75;
        }
        $img->cropThumbnailImage($new_width, $new_height);
        $img->setImageFormat("jpeg");
        $img->setCompressionQuality(65);
        $img->setImageFilename($thumbnail);
        $img->WriteImage();
        $img->clear();
        $img->destroy();
    } else {
        // Create video thumbnail
        exec("ffmpegthumbnailer -i \"$filename\" -o \"$thumbnail\" -t 1 -s $thumb_size -f");        
    }

    $m_db->close();
    return true;
}

function updateFolderThumbnail($id)
{
    global $thumb_size;
    global $thumb_folder;
    global $image_folder;
    global $folder_thumbname;

    $filename  = "";
    $thumbnail = "";
    $p_id      = -1;

    $m_db = new mediaDB();

    set_time_limit(30); // Set time limit to avoid timeout

    // Get Folder info
    $result = $m_db->querySingle("SELECT thumbnail FROM media_folders WHERE id=$id;", true);
    if ($result === false) throw new Exception($m_db->lastErrorMsg());
    if ($result['thumbnail'] != $folder_thumbname) return false; // Only generate thumbnails for pure folder images
    $filename  = $result['thumbnail'];
    $thumbnail = $result['thumbnail'];
    $filename  = $image_folder.'/'.$m_db->getMediaFolderPath($id).'/'.$filename;
    $thumbnail = $thumb_folder.'/'.$m_db->getMediaFolderPath($id).'/'.$thumbnail;

    if (file_exists($thumbnail) && (filemtime($thumbnail) > filemtime($filename))) return false; // No need to update

    // Create picture thumbnail
    // load image and get image size
    if (!extension_loaded('imagick')) die("-E-   php_imagick extension is required !\n");
    if (getenv('MAGICK_THREAD_LIMIT') == "") die("-E-   This script requires the MAGICK_THREAD_LIMIT=1 line to be added in /etc/environment !\n");
    $img    = new Imagick();
    $img->ReadImage($filename);
    $width  = $img->GetImageWidth();
    $height = $img->GetImageHeight();
    // calculate thumbnail size
    if ($width >= $height) {
        $new_width  = $thumb_size;
        $new_height = $thumb_size * 0.75;
    } else {
        $new_height = $thumb_size;
        $new_width  = $thumb_size * 0.75;
    }
    $img->cropThumbnailImage($new_width, $new_height);
    $img->setImageFormat("jpeg");
    $img->setCompressionQuality(65);
    $img->setImageFilename($thumbnail);
    $img->WriteImage();
    $img->clear();
    $img->destroy();

    $m_db->close();
    return true;
}


?>
