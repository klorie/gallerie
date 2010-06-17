<?php

// Start PHP code
require "common.php";
require "config.php";

function processDirectory( $path )
{
    global $thumb_folder;
    global $image_folder;
    global $resize_folder;
    global $thumb_size;

    // open the directory
    $dir = opendir("$image_folder/$path");

    // loop through it, looking for any/all JPG files:
    while (false !== ($fname = readdir($dir))) {
        // parse path for the extension
        $info = pathinfo("$image_folder/$path/$fname");
        $ext = strtolower($info['extension']);
        $fname_noext = $info['filename'];
        // Fix for php < 5.2
        if ($fname_noext == "" ) {
            $fname_noext = substr($info['basename'], 0, strlen($info['basename'])-4);
        }
        if ($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'bmp') {
            // Check if thumbnail already exist (whatever extension)
            if (!file_exists("$thumb_folder/$path/$fname_noext".'.jpg'))
                createThumb($path, $fname);
            if (!file_exists("$resize_folder/$path/$fname_noext".'.jpg'))
                createResize($path, $fname);
        }
        if ($ext == 'avi' || $ext == 'mov' || $ext =='mpg') {
            if (!file_exists("$thumb_folder/$path/$fname_noext".'.jpg') || 
                (filemtime("$image_folder/$path/$fname") > filemtime("$thumb_folder/$path/$fname_noext".'.jpg')))
                exec("ffmpegthumbnailer -i $image_folder/$path/$fname -o $thumb_folder/$path/$fname_noext.jpg -t 1 -s $thumb_size -f");
            if (!file_exists("$resize_folder/$path/$fname_noext".'.flv') ||
                (filemtime("$image_folder/$path/$fname") > filemtime("$thumb_folder/$path/$fname_noext".'.flv')))
                exec("mencoder $image_folder/$path/$fname -o $resize_folder/$path/$fname_noext.flv -of lavf -oac mp3lame -lameopts abr:br=64:mode=3 -ovc lavc -lavcopts vcodec=flv:vbitrate=1600:mbd=2:mv0:trell:v4mv:cbp:last_pred=4 -ofps 15 -srate 44100");
        }
    }
    // close the directory
    closedir($dir);
}

function cleanDirectory($path, $base_folder)
{
    global $image_folder;

    // open the thumbnail directory
    $dir = opendir("$base_folder/$path");

    // loop through it
    while (false !== ($fname = readdir($dir))) {
        if (is_file($fname)) {
            // Check if file exist
            $info = pathinfo("$image_folder/$path/$fname");
            $fname_noext = $info['filename'];
            // Fix for php < 5.2
            if ($fname_noext == "" )
                $fname_noext = substr($info['basename'], 0, strlen($info['basename'])-4);

            if (count(glob("$image_folder/$path/$fname_noext.*")) != 0)
                continue;
            else {
                // Image not found, removing thumbnail
                echo "$image_folder/$path/$fname_noext not found, removing generated file\n";
                unlink("$base_folder/$path/$fname");
            }
            closedir($dir);
            // Remove folder if empty
            $fcount = count(scandir($base_folder/$path));
            if ($fcount == 2) {
                echo "$base_folder/$path empty, removing\n";
                rmdir("$base_folder/$path");
            } else {
                echo "$base_folder/$path contains $fcount elements\n";
            }
        }
    }
}        

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

$clean = false;
if ((isset($_REQUEST['clean']))) {
    $clean = $_REQUEST['clean'];
}
// Free does not allow directories to be removed, only files.
//
if ($argv[1] == "--clean" || $clean) {
    // Clean all unneeded thumbnails
    echo "Removing all thumbnails\n";
    deleteDir("$thumb_folder") ;
    mkdir("$thumb_folder");
    echo "Removing all resized\n";
    deleteDir("$resize_folder");
}

$dirlist = getFileList("", true, 10, $image_folder, true);
echo "Found ".count($dirlist[dir])." folders to process\n";

foreach($dirlist[dir] as $file) {
    $path = $file['fullname'];
    if (!file_exists("$thumb_folder/$path")) { 
        mkdir("$thumb_folder/$path", 0777, true);
    }
    if (!file_exists("$resize_folder/$path")) {
        mkdir("$resize_folder/$path", 0777, true); 
    }
    echo "Processing $path...";
    cleanDirectory($path, $thumb_folder);
    cleanDirectory($path, $resize_folder);
    processDirectory($path);
    echo " [ Done ]\n";
}

$mtime = explode(' ', microtime());
$totaltime = ($mtime[0] + $mtime[1] - $starttime) / 60;
if ($totaltime < 60) {
    echo "Processing done in $totaltime min\n";
} else {
    $totaltime = $totaltime / 60;
    echo "Processing done in $totaltime hours\n";
}

?>
