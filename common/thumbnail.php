<?php

//! Return thumbnail path in form of AA/BB.jpg with HEX formatting of $id. 
//! $folder selects between element and folder thumbnail
function getThumbnailPath($id, $folder=false)
{
    global $thumb_folder;

    // Return thumbnail path in form of AA/BB.jpg with HEX formatting of $id
    $hexid    = sprintf("%04X", $id);
    $hexsplit = str_split($hexid, 2);
    if ($folder == true)
        return "$thumb_folder/folders/$hexsplit[0]/$hexsplit[1].jpg";
    else
        return "$thumb_folder/elements/$hexsplit[0]/$hexsplit[1].jpg";
}

function updateThumbnail(mediaDB &$db = NULL, $id)
{
    global $BASE_DIR;
    global $thumb_size;
    global $image_folder;

    $filename  = "";
    $thumbnail = "";
    $type      = "";
    $lastmod   = "";
    $p_id      = -1;

    set_time_limit(300); // Set time limit to avoid timeout

    $m_db    = NULL;
    if ($db == NULL)
        $m_db = new mediaDB();
    else
        $m_db = $db;

    // Get Object info
    $row = $m_db->query("SELECT folder_id, filename, type, lastmod FROM media_objects WHERE id=$id;", true);
    if ($row === false) throw new Exception($m_db->error);
    $result    = $row->fetch_assoc();
    $row->free();
    $filename  = $result['filename'];
    $type      = $result['type'];
    $lastmod   = $result['lastmod'];
    $p_id      = $result['folder_id'];
    // Retreive full path
    $filename  = "$BASE_DIR/$image_folder/".$m_db->getFolderPath($p_id)."/$filename";
    $thumbnail = "$BASE_DIR/".getThumbnailPath($id);

    if (file_exists($thumbnail) && (filemtime($thumbnail) > strftime($lastmod))) return false; // No need to update

    // Create required thumbnail location if required
    if (is_dir(dirname($thumbnail)) == false) {
        print "-D-    ".dirname($thumbnail)." not found, creating.\n";
        mkdir(dirname($thumbnail), 0777, true);
    }

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
    } 
    // else {
    //    // Create video thumbnail
    //    exec("ffmpegthumbnailer -i \"$filename\" -o \"$thumbnail\" -t 1 -s $thumb_size -f");        
    //}

    if ($db == NULL)
        $m_db->close();

    return true;
}

function updateFolderThumbnail(mediaDB &$db, $id)
{
    global $BASE_DIR;
    global $thumb_size;
    global $image_folder;
    global $folder_thumbname;

    $filename       = "";
    $thumbnail      = "";
    $p_id           = -1;
    $thumbnail_size = $thumb_size;

    $m_db    = NULL;
    if ($db == NULL)
        $m_db = new mediaDB();
    else
        $m_db = $db;

    // Sanity check - no thumbnail for root folder
    if ($id == -1) return false;

    set_time_limit(300); // Set time limit to avoid timeout

    // Get Folder info
    $result = $m_db->query("SELECT thumbnail_source, parent_id FROM media_folders WHERE id=$id;", true);
    if ($result === false) throw new Exception($m_db->error());
    $row = $result->fetch_assoc();
    $result->free();
    if ($row['parent_id'] == -1) $thumbnail_size *= 2; // Generate twice as bigger thumbnails for top level folders
    $filename  = $row['thumbnail_source'];
    $filename  = "$BASE_DIR/$image_folder/".$m_db->getFolderPath($id)."/$filename";
    $thumbnail = "$BASE_DIR/".getThumbnailPath($id, true);

    if (file_exists($thumbnail) && (filemtime($thumbnail) > filemtime($filename))) return false; // No need to update

    $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext == '') print "-D- $filename\n";
    if ($ext == 'jpg' || $ext == 'png' || $ext == 'gif' || $ext == 'bmp')
        $type = 'picture';
    else
        $type = 'movie';
    
    // Create required thumbnail location if required
    if (is_dir(dirname($thumbnail)) == false) {
        print "-I-    Thumbnail folder ".dirname($thumbnail)." not found, creating.\n";
        mkdir(dirname($thumbnail), 0755, true);
    }

    if ($type == 'picture') {
        // Create picture thumbnail
        // load image and get image size
        if (!extension_loaded('imagick')) die("-E-   php_imagick extension is required !\n");
        $img    = new Imagick();
        $img->ReadImage($filename);
        $width  = $img->GetImageWidth();
        $height = $img->GetImageHeight();
        // calculate thumbnail size
        if ($width >= $height) {
            $new_width  = $thumbnail_size;
            $new_height = $thumbnail_size * 0.75;
        } else {
            $new_height = $thumbnail_size;
            $new_width  = $thumbnail_size * 0.75;
        }
        if ($row['parent_id'] == -1)
            $img->cropThumbnailImage($thumbnail_size, $thumbnail_size * 0.75);
        else
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

    if ($db == NULL)
        $m_db->close();

    return true;
}

?>
